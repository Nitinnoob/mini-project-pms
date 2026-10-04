<?php
/**
 * Endpoint: generate_group_report.php?project_id=123
 *
 * Generates the complete, official VTU Computer Science & Engineering Mini-Project Report
 * formatted strictly per university guidelines (Cover Page, Certificate, Declaration,
 * Acknowledgement, Abstract <= 100 words, Table of Contents, Chapters 1-6, References,
 * Photo Gallery, and Team Members Bio Data Table).
 *
 * Auth: Accessible by project members, assigned mentor, or the classroom coordinator.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized. Please log in.');
}

// Support both local code snippet path and root pjtmgmt deployment
if (file_exists(__DIR__ . '/../dbs.php')) {
    require __DIR__ . '/../dbs.php';
} elseif (file_exists(__DIR__ . '/dbs.php')) {
    require __DIR__ . '/dbs.php';
} else {
    require __DIR__ . '/db.php';
}

require_once __DIR__ . '/generate_docx.php';

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    http_response_code(400);
    exit('Missing project_id');
}

$userId = (int)$_SESSION['user_id'];

// --- Fetch project + mentor + classroom ---
$stmt = $pdo->prepare("
    SELECT p.id, p.name AS project_name, p.description AS project_description,
           p.classroom_id, p.mentor_id,
           c.name AS classroom_name, c.created_by AS coordinator_id,
           m.username AS mentor_name,
           coord.username AS coordinator_name
    FROM projects p
    JOIN classrooms c ON p.classroom_id = c.id
    LEFT JOIN users m ON p.mentor_id = m.id
    LEFT JOIN users coord ON c.created_by = coord.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(404);
    exit('Project not found.');
}

// --- Auth check: project member, mentor, or coordinator ---
$isCoordinator = ($userId === (int)$project['coordinator_id']);
$isMentor = ($userId === (int)$project['mentor_id']);

$memberStmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
$memberStmt->execute([$projectId, $userId]);
$isMember = (bool)$memberStmt->fetchColumn();

if (!$isCoordinator && !$isMentor && !$isMember) {
    http_response_code(403);
    exit('Not authorized to generate report for this project.');
}

// --- Fetch members with USN from classroom_members ---
$stmt = $pdo->prepare("
    SELECT u.username AS name, COALESCE(cm.usn, '1RG24CS---') AS usn, pm.is_leader
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    JOIN users u ON pm.user_id = u.id
    LEFT JOIN classroom_members cm ON cm.classroom_id = p.classroom_id AND cm.user_id = u.id
    WHERE pm.project_id = ?
    ORDER BY pm.is_leader DESC, u.username ASC
");
$stmt->execute([$projectId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupData = [
    'department'           => 'Department of Computer Science and Engineering',
    'academic_year'        => '2026-2027',
    'section'              => '5th Semester A and B Section',
    'project_name'         => $project['project_name'],
    'project_description'  => $project['project_description'] ?: 'Academic mini-project implementation for VTU curriculum.',
    'mentor_name'          => $project['mentor_name'] ?? 'Dr. Latha P H',
    'coordinator_name'     => $project['coordinator_name'] ?? 'Dr. Latha P H',
    'hod_name'             => 'Dr. Rathishchandra R Gatti',
    'members'              => $members,
];

$templateFile = __DIR__ . '/templates/report_template.docx';
$result = generate_group_report_docx(
    $groupData,
    file_exists($templateFile) ? $templateFile : '',
    __DIR__ . '/generated_reports'
);

if (!$result['success']) {
    http_response_code(500);
    exit('Error generating report: ' . htmlspecialchars($result['error']));
}

$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $project['project_name']) . '_VTU_Report.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($result['file']));
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
ob_clean();
flush();
readfile($result['file']);

// Remove generated file after streaming
@unlink($result['file']);
exit;
