<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'phase_engine.php';
require_once 'notify.php';
require_once 'repositories/project_repository.php';
require_once 'repositories/membership_repository.php';
require_once 'repositories/weekly_log_repository.php';
require_once 'repositories/meeting_repository.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

$projectId   = (int)($_POST['project_id'] ?? 0);
$classroomId = (int)($_POST['classroom_id'] ?? 0);
$weekNumber  = (int)($_POST['week_number'] ?? 0);
$workDone    = trim((string)($_POST['work_done'] ?? ($_POST['work_summary'] ?? '')));
$nextSteps   = trim((string)($_POST['next_steps'] ?? ''));
$blockers    = trim((string)($_POST['blockers'] ?? ''));

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($projectId <= 0 || $weekNumber <= 0 || $classroomId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid project, classroom, or week number.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Invalid project, classroom, or week number.';
    header("Location: dashboard.php");
    exit;
}

// 1. Authorization: ANY active project member (Leader or Student) can submit
if (!is_active_project_member($pdo, $projectId, $currentUserId, $classroomId)) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Only active project team members can submit weekly updates.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Unauthorized: Only active project team members can submit weekly updates.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId));
    exit;
}

// Team size guard for final week
$crsDates = project_find_schedule($pdo, $projectId, $classroomId);
$totalWeeks = 1;
if ($crsDates && !empty($crsDates['start_date']) && !empty($crsDates['end_date'])) {
    $totalWeeks = phase_total_weeks($crsDates['start_date'], $crsDates['end_date']);
}
$minTeamSize = classroom_get_min_team_size($pdo, $projectId);
$activeCount = project_member_active_count($pdo, $projectId);

if ($weekNumber === $totalWeeks && $activeCount < $minTeamSize) {
    if ($isAjax) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => "Team must meet minimum size ({$minTeamSize}) before final week submission."]);
        exit;
    }
    $_SESSION['flash_error'] = "Team must meet the minimum size requirement ({$minTeamSize} members) before final week submission.";
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

try {
    $pdo->beginTransaction();

    // Ensure Saturday meeting row exists in weekly_meetings
    $meeting = meeting_find_by_week($pdo, $projectId, $weekNumber);
    if (!$meeting) {
        if ($crsDates && !empty($crsDates['start_date']) && !empty($crsDates['end_date'])) {
            meeting_ensure_project_meetings($pdo, $projectId, $crsDates['start_date'], $crsDates['end_date']);
        }
        $meeting = meeting_find_by_week($pdo, $projectId, $weekNumber);
    }

    if (!$meeting) {
        // Fallback: derive meeting date or default to today
        $meetingDate = date('Y-m-d');
        $stmtIns = $pdo->prepare("
            INSERT INTO weekly_meetings (project_id, week_number, meeting_date, status)
            VALUES (?, ?, ?, 'scheduled')
        ");
        $stmtIns->execute([$projectId, $weekNumber, $meetingDate]);
        $meetingId = (int)$pdo->lastInsertId();
    } else {
        $meetingId = (int)$meeting['id'];
    }

    // Submit team update into weekly_meetings capturing submitted_by
    meeting_submit_team_update($pdo, $meetingId, $currentUserId, [
        'work_done'  => $workDone,
        'next_steps' => $nextSteps,
        'blockers'   => $blockers,
    ]);

    // Also sync to legacy weekly_submissions for backwards compatibility
    $existingLegacy = weekly_submission_get_status($pdo, $projectId, $weekNumber);
    $submissionId = weekly_submission_save(
        $pdo,
        $projectId,
        $weekNumber,
        $currentUserId,
        $workDone,
        $nextSteps,
        $existingLegacy ? (int)$existingLegacy['id'] : null,
        $existingLegacy ? $existingLegacy['status'] : null
    );

    // 5. Secure File Upload Handling (MIME inspection, size limits, unguessable filenames)
    $uploadedFiles = [];
    if (!empty($_FILES['weekly_files']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/meetings/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $htaccessFile = $uploadDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents(
                $htaccessFile,
                "RemoveHandler .php .phtml .php3 .php4 .php5 .phar .shtml .cgi .pl\n" .
                "RemoveType .php .phtml .php3 .php4 .php5 .phar .shtml .cgi .pl\n" .
                "php_flag engine off\n" .
                "Options -ExecCGI -Indexes\n" .
                "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|shtml|cgi|pl)$\">\n" .
                "Order Deny,Allow\n" .
                "Deny from all\n" .
                "</FilesMatch>\n"
            );
        }

        $allowedExtMap = [
            'pdf'  => ['application/pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'doc'  => ['application/msword'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
            'ppt'  => ['application/vnd.ms-powerpoint'],
            'zip'  => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
            'txt'  => ['text/plain'],
            'md'   => ['text/plain', 'text/markdown'],
            'csv'  => ['text/plain', 'text/csv', 'application/vnd.ms-excel'],
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
        ];

        $maxFileSize = 10 * 1024 * 1024; // 10 MB per file

        $names = $_FILES['weekly_files']['name'];
        $tmpNames = $_FILES['weekly_files']['tmp_name'];
        $errors = $_FILES['weekly_files']['error'];
        $sizes = $_FILES['weekly_files']['size'];

        foreach ($names as $idx => $rawName) {
            if ($errors[$idx] !== UPLOAD_ERR_OK || empty($tmpNames[$idx])) {
                continue;
            }

            $fileSize = (int)$sizes[$idx];
            if ($fileSize <= 0 || $fileSize > $maxFileSize) {
                continue; // Enforce size limit
            }

            $rawBasename = basename((string)$rawName);
            $ext = strtolower(pathinfo($rawBasename, PATHINFO_EXTENSION));

            if (!array_key_exists($ext, $allowedExtMap)) {
                continue; // Disallow dangerous/unsupported extensions
            }

            // MIME inspection
            $detectedMime = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detectedMime = finfo_file($finfo, $tmpNames[$idx]);
                    finfo_close($finfo);
                }
            }
            if (!$detectedMime && function_exists('mime_content_type')) {
                $detectedMime = mime_content_type($tmpNames[$idx]);
            }

            // Validate detected MIME matches allowed MIME list for this extension
            $isValidMime = false;
            if ($detectedMime && in_array($detectedMime, $allowedExtMap[$ext], true)) {
                $isValidMime = true;
            }

            if (!$isValidMime) {
                continue; // MIME check failed
            }

            // Generate unguessable stored filename
            $sanitizedOrig = preg_replace('/[^a-zA-Z0-9_\.\-\s]/u', '_', $rawBasename);
            $storedName = 'meet_' . $meetingId . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
            $targetPath = $uploadDir . $storedName;
            $relPath = 'uploads/meetings/' . $storedName;

            if (move_uploaded_file($tmpNames[$idx], $targetPath)) {
                $fileId = meeting_file_add(
                    $pdo,
                    $meetingId,
                    $sanitizedOrig,
                    $storedName,
                    $relPath,
                    $fileSize,
                    $detectedMime
                );

                if ($submissionId > 0) {
                    weekly_submission_add_file($pdo, $submissionId, $sanitizedOrig, $relPath);
                }

                $uploadedFiles[] = [
                    'id'            => $fileId,
                    'original_name' => $sanitizedOrig,
                    'file_path'     => $relPath,
                    'file_size'     => $fileSize,
                ];
            }
        }
    }

    $pdo->commit();

    // Notify project mentor
    $submitterName = $_SESSION['username'] ?? 'A team member';
    $projectName = notify_project_name($pdo, $projectId);
    notify_project_mentor(
        $pdo,
        $projectId,
        'weekly_log',
        "{$submitterName} submitted the Week {$weekNumber} meeting update for {$projectName}.",
        "dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'        => true,
            'message'        => 'Weekly update submitted successfully.',
            'meeting_id'     => $meetingId,
            'week_number'    => $weekNumber,
            'uploaded_files' => $uploadedFiles,
        ]);
        exit;
    }

    $_SESSION['flash_success'] = "Week {$weekNumber} update submitted successfully.";
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'A server error occurred while saving the update.']);
        exit;
    }
    $_SESSION['flash_error'] = 'A server error occurred while saving the update.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}
