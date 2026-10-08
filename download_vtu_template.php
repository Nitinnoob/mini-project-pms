<?php
/**
 * VTU Project Report (.docx) generator.
 *
 * Generates official VTU-formatted project reports pre-filled with:
 * - Cover page & Title metadata
 * - Certificate with Guide, Coordinator, and HOD sign-off
 * - Declaration with student USNs and sign spaces
 * - Acknowledgement & Abstract
 * - Chapters 1-6 Scaffolding (Introduction, Literature Survey & SRS,
 *   System Design, Implementation, Testing & Results, Conclusion)
 * - IEEE References & Photo Gallery
 * - Team Members Bio-Data Table
 * - APPENDIX C: Continuous Internal Evaluation (CIE) Marks Sheet (Report /50,
 *   Presentation /25, Viva Q&A /25, Total /100) & Complete Saturday Weekly Guide
 *   Meeting Review Logs with attendance records and actionable directives.
 *
 * Supports swappable .docx templates (configured in config/report.php or templates/).
 */
require_once 'bootstrap.php';
require_once 'generate_docx.php';
require_once 'repositories/meeting_repository.php';
require_once 'repositories/marks_repository.php';

require_login();

$uid          = (int)$_SESSION['user_id'];
$classroom_id = (int)($_GET['classroom_id'] ?? 0);
$project_id   = (int)($_GET['project_id'] ?? 0);

if (!$classroom_id || !$project_id) {
    http_response_code(400);
    exit('Missing classroom_id or project_id');
}

// 1. Authorize: user must be in classroom
$stmtM = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
$stmtM->execute([$classroom_id, $uid]);
$classroomRole = $stmtM->fetchColumn();
if (!$classroomRole) {
    http_response_code(403);
    exit('Not authorized to access this classroom.');
}

// 2. Fetch project, classroom, mentor, and coordinator details
$stmtP = $pdo->prepare("
    SELECT p.id, p.name AS project_name, p.description AS project_description,
           p.classroom_id, p.mentor_id,
           c.name AS classroom_name, c.created_by AS coordinator_id,
           c.start_date, c.end_date,
           m.username AS mentor_name,
           coord.username AS coordinator_name
    FROM projects p
    JOIN classrooms c ON p.classroom_id = c.id
    LEFT JOIN users m ON p.mentor_id = m.id
    LEFT JOIN users coord ON c.created_by = coord.id
    WHERE p.id = ? AND p.classroom_id = ?
");
$stmtP->execute([$project_id, $classroom_id]);
$proj = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$proj) {
    http_response_code(404);
    exit('Project not found.');
}

// Check authorization: Admin, assigned mentor, coordinator, or active project member
$isCoordinator = ($uid === (int)$proj['coordinator_id']);
$isMentor = ($uid === (int)$proj['mentor_id']);
$isAdmin = ($classroomRole === 'Admin');

$stmtOn = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
$stmtOn->execute([$project_id, $uid]);
$isMember = (bool)$stmtOn->fetch();

if (!$isAdmin && !$isCoordinator && !$isMentor && !$isMember) {
    http_response_code(403);
    exit('Not authorized to generate report for this project.');
}

// 3. Load Report Configuration Defaults
$reportConfig = file_exists(__DIR__ . '/config/report.php') ? require __DIR__ . '/config/report.php' : [];

$university      = $reportConfig['university'] ?? 'Visvesvaraya Technological University';
$universityAddr  = $reportConfig['university_addr'] ?? 'Jnana Sangama, Belagavi - 590 018';
$collegeName     = $reportConfig['college_name'] ?? 'Sahyadri College of Engineering & Management';
$department      = $reportConfig['department'] ?? 'Department of Computer Science and Engineering';
$branch          = $reportConfig['branch'] ?? 'Computer Science and Engineering';
$courseName      = $reportConfig['course_name'] ?? 'Mini-Project';
$courseCode      = $reportConfig['course_code'] ?? '21CSMP58';
$academicYear    = $reportConfig['academic_year'] ?? '2026-2027';
$section         = $reportConfig['section'] ?? '5th Semester A and B Section';
$hodName         = $reportConfig['hod_name'] ?? 'Dr. Rathishchandra R Gatti';

// 4. Fetch Active Team Members with USN
$stmtMem = $pdo->prepare("
    SELECT u.id, u.username AS name, COALESCE(cm.usn, '1RG24CS---') AS usn, pm.is_leader
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    JOIN users u ON pm.user_id = u.id
    LEFT JOIN classroom_members cm ON cm.classroom_id = p.classroom_id AND cm.user_id = u.id
    WHERE pm.project_id = ? AND pm.join_status = 'Active'
    ORDER BY pm.is_leader DESC, u.username ASC
");
$stmtMem->execute([$project_id]);
$membersRaw = $stmtMem->fetchAll(PDO::FETCH_ASSOC);

$members = [];
foreach ($membersRaw as $m) {
    $members[] = [
        'id'        => (int)$m['id'],
        'name'      => $m['name'],
        'usn'       => $m['usn'] ?: '1RG24CS---',
        'is_leader' => (bool)$m['is_leader'],
    ];
}
if (empty($members)) {
    $members[] = ['id' => $uid, 'name' => 'Student Name', 'usn' => '1RG24CS001', 'is_leader' => true];
}

// 5. Fetch Weekly Meetings Data
$meetingsRaw = meeting_get_by_project($pdo, $project_id);
if (empty($meetingsRaw)) {
    // If no meetings generated yet, ensure they exist
    meeting_ensure_project_meetings($pdo, $project_id, $proj['start_date'] ?? null, $proj['end_date'] ?? null);
    $meetingsRaw = meeting_get_by_project($pdo, $project_id);
}

$weeklyMeetings = [];
$totalHeld = 0;
foreach ($meetingsRaw as $m) {
    $wkNum = (int)$m['week_number'];
    $status = $m['status'];
    if ($status === 'held') {
        $totalHeld++;
    }

    $teamUpdate = $m['team_update_parsed'] ?? meeting_decode_team_update($m['team_update'] ?? '');
    $workDone = !empty($teamUpdate['work_done']) ? $teamUpdate['work_done'] : (!empty($m['team_update']) ? $m['team_update'] : '');
    $nextSteps = $teamUpdate['next_steps'] ?? '';
    $blockers = $teamUpdate['blockers'] ?? '';

    // Attendance summary for this meeting
    $attList = $m['attendance'] ?? [];
    $presCount = 0;
    $totalAtt = count($attList);
    foreach ($attList as $a) {
        if (($a['status'] ?? '') === 'present') {
            $presCount++;
        }
    }
    $attSummaryStr = ($totalAtt > 0) ? "$presCount/$totalAtt Present" : ucfirst($status);

    $weeklyMeetings[] = [
        'week_number'        => $wkNum,
        'meeting_date'       => $m['meeting_date'] ?: "Week $wkNum",
        'status'             => $status,
        'work_done'          => $workDone ?: 'In progress',
        'next_steps'         => $nextSteps ?: 'Follow-up implementation',
        'blockers'           => $blockers ?: 'None',
        'guide_feedback'     => $m['guide_feedback'] ?: 'Reviewed and discussed with team',
        'instructions'       => $m['instructions'] ?? [],
        'attendance_summary' => $attSummaryStr,
    ];
}

// 6. Fetch Continuous Internal Evaluation (CIE) Marks
$evalSheet = marks_get_full_evaluation_sheet($pdo, $project_id);
$projectMarks = $evalSheet['project_marks'] ?? ['report_marks' => null];
$reportMarks = $projectMarks['report_marks'];

$evalStudentsByUid = [];
foreach (($evalSheet['students'] ?? []) as $st) {
    $evalStudentsByUid[(int)$st['user_id']] = $st;
}

$studentEvaluations = [];
foreach ($members as $mem) {
    $memId = $mem['id'];
    $sMarks = $evalStudentsByUid[$memId] ?? null;

    $presMarks = $sMarks['presentation_marks'] ?? null;
    $qaMarks = $sMarks['qa_marks'] ?? null;
    $totMarks = $sMarks['total_marks'] ?? null;

    // Student personal attendance percentage
    $attStats = meeting_calculate_student_attendance($pdo, $project_id, $memId);

    $studentEvaluations[] = [
        'name'               => $mem['name'],
        'usn'                => $mem['usn'],
        'report_marks'       => $reportMarks,
        'presentation_marks' => $presMarks,
        'qa_marks'           => $qaMarks,
        'total_marks'        => $totMarks,
        'attendance_pct'     => $attStats['percentage'] ?? 100.0,
    ];
}

// Overall Project Attendance Metrics
$avgAttPct = 100.0;
if (!empty($studentEvaluations)) {
    $sum = array_sum(array_column($studentEvaluations, 'attendance_pct'));
    $avgAttPct = round($sum / count($studentEvaluations), 1);
}

$attendanceSummary = [
    'total_meetings' => count($weeklyMeetings),
    'held'           => $totalHeld,
    'percentage'     => $avgAttPct,
];

// 7. Swappable Template Resolution
// Check user-requested template, config template, or default in templates/
$requestedTemplate = trim((string)($_GET['template'] ?? ''));
$templateFile = '';

if ($requestedTemplate !== '') {
    $candidate = __DIR__ . '/templates/' . basename($requestedTemplate);
    if (file_exists($candidate)) {
        $templateFile = $candidate;
    }
}
if (empty($templateFile)) {
    $defaultTpl = $reportConfig['default_template'] ?? (__DIR__ . '/templates/report_template.docx');
    if (file_exists($defaultTpl)) {
        $templateFile = $defaultTpl;
    }
}

// 8. Assemble Full Context
$groupData = [
    'university'          => $university,
    'university_addr'     => $universityAddr,
    'college_name'        => $collegeName,
    'department'          => $department,
    'branch'              => $branch,
    'course_name'         => $courseName,
    'course_code'         => $courseCode,
    'academic_year'       => $academicYear,
    'section'             => $section,
    'project_name'        => $proj['project_name'],
    'project_description' => $proj['project_description'] ?: 'Academic mini-project implementation for VTU curriculum.',
    'mentor_name'         => $proj['mentor_name'] ?? 'Faculty Guide',
    'coordinator_name'    => $proj['coordinator_name'] ?? 'Project Coordinator',
    'hod_name'            => $hodName,
    'members'             => $members,
    'weekly_meetings'     => $weeklyMeetings,
    'student_evaluations' => $studentEvaluations,
    'attendance_summary'  => $attendanceSummary,
];

// 9. Generate Document via docxtpl Bridge
$result = generate_group_report_docx($groupData, $templateFile);

if (!$result['success'] || empty($result['file']) || !file_exists($result['file'])) {
    http_response_code(500);
    exit('Error generating project report: ' . htmlspecialchars($result['error'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8'));
}

$generatedFile = $result['file'];
$downloadFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $proj['project_name']) . '_VTU_Report.docx';

// 10. Stream Document to Client
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
header('Content-Length: ' . filesize($generatedFile));
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

if (ob_get_level()) {
    ob_end_clean();
}
readfile($generatedFile);
@unlink($generatedFile);
exit;
