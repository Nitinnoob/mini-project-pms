<?php
require_once 'bootstrap.php';
require_once 'phase_engine.php';
require_once 'notify.php';
require_once 'repositories/project_repository.php';
require_once 'repositories/membership_repository.php';
require_once 'repositories/weekly_log_repository.php';

require_login();

if (is_post()) {
    csrf_verify();

    $project_id = $_POST['project_id'] ?? null;
    $classroom_id = $_POST['classroom_id'] ?? null;
    $week_number = $_POST['week_number'] ?? null;
    $work_summary = $_POST['work_summary'] ?? '';
    $next_steps = $_POST['next_steps'] ?? '';
    
    if (!$project_id || !$week_number || !$classroom_id) {
        header("Location: dashboard.php");
        exit;
    }
    
    // Verify user is in this project (Leader ideally, but any member can theoretically submit)
    if (!is_active_project_member($pdo, $project_id, $_SESSION['user_id'])) {
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
    
    // Check if a submission already exists and its review status
    $existing = weekly_submission_get_status($pdo, (int)$project_id, (int)$week_number);

    // Calculate total weeks for the classroom to determine if this is the final week
    $crsDates = project_find_schedule($pdo, (int)$project_id, (int)$classroom_id);
    $totalWeeks = 1;
    if ($crsDates && !empty($crsDates['start_date']) && !empty($crsDates['end_date'])) {
        $totalWeeks = phase_total_weeks($crsDates['start_date'], $crsDates['end_date']);
    }

    // Get min team size for this classroom
    $minTeamSize = classroom_get_min_team_size($pdo, (int)$project_id);

    // Count active members for this project
    $activeCount = project_member_active_count($pdo, (int)$project_id);

    // If this is the final week and team is understaffed, block submission
    if ($week_number == $totalWeeks && $activeCount < $minTeamSize) {
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&error=min_team_size");
        exit;
    }

    if ($existing && $existing['status'] === 'approved') {
        // Cannot modify an approved log
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&error=log_approved");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $submission_id = weekly_submission_save(
            $pdo,
            (int)$project_id,
            (int)$week_number,
            (int)$_SESSION['user_id'],
            $work_summary,
            $next_steps,
            $existing ? (int)$existing['id'] : null,
            $existing ? $existing['status'] : null
        );
        
        // Handle file uploads
        if (!empty($_FILES['weekly_files']['name'][0])) {
            $uploadDir = 'uploads/weekly/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                // Secure upload dir
                file_put_contents($uploadDir . '.htaccess', "RemoveHandler .php .phtml .php3 .php4 .php5\nRemoveType .php .phtml .php3 .php4 .php5\nphp_flag engine off\nOptions -ExecCGI\n");
            }
            
            $allowedExts = ['pdf', 'docx', 'zip', 'pptx', 'txt', 'png', 'jpg', 'jpeg'];
            
            foreach ($_FILES['weekly_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['weekly_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalName = basename($_FILES['weekly_files']['name'][$key]);
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowedExts)) {
                        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                        $fileName = time() . '_' . rand(1000,9999) . '_' . $safeName;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $filePath)) {
                            weekly_submission_add_file($pdo, $submission_id, $originalName, $filePath);
                        }
                    }
                }
            }
        }
        
        $pdo->commit();

        notify_project_mentor($pdo, $project_id, 'weekly_log',
            htmlspecialchars_decode($_SESSION['username'] ?? 'A teammate') . ' submitted the Week ' . (int)$week_number . ' log for ' . notify_project_name($pdo, $project_id) . '.',
            'dashboard.php?classroom_id=' . urlencode($classroom_id) . '&project_id=' . urlencode($project_id), $_SESSION['user_id']);
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
    exit;
}
