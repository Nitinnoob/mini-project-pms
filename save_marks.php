<?php
// save_marks.php - CIE 50/25/25 Scoring Engine, Range Validation & Finalize Lock
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

$evalConfig = require __DIR__ . '/config/evaluation.php';
require_teacher();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$projectId = (int)($_POST['project_id'] ?? 0);
$reportMarks = isset($_POST['report_marks']) && $_POST['report_marks'] !== '' ? (float)$_POST['report_marks'] : null;
$finalizeAction = isset($_POST['is_finalized']) ? (int)$_POST['is_finalized'] : 0;
$justificationReason = trim($_POST['audit_reason'] ?? '');
$studentMarksInput = $_POST['student_marks'] ?? []; // student_id => ['presentation' => ..., 'qa' => ...]

if ($projectId <= 0) {
    set_flash('error', 'Invalid project selected.');
    header('Location: dashboard.php');
    exit;
}

// Fetch project & teacher authorization
$pStmt = $pdo->prepare("
    SELECT p.*, c.teacher_id, pm.is_finalized, pm.report_marks as old_report_marks
    FROM projects p
    JOIN classrooms c ON p.classroom_id = c.id
    LEFT JOIN project_marks pm ON p.id = pm.project_id
    WHERE p.id = ?
");
$pStmt->execute([$projectId]);
$project = $pStmt->fetch();

if (!$project) {
    set_flash('error', 'Project not found.');
    header('Location: dashboard.php');
    exit;
}

if ($project['mentor_id'] != $user['id'] && $project['teacher_id'] != $user['id']) {
    set_flash('error', 'Access denied: Only the assigned faculty guide or coordinator can award CIE marks.');
    header('Location: dashboard.php');
    exit;
}

// Validate Report Marks range (0 to 50)
$maxReport = $evalConfig['marks']['report_max'];
$maxPres   = $evalConfig['marks']['presentation_max'];
$maxQa     = $evalConfig['marks']['qa_max'];

if ($reportMarks !== null && ($reportMarks < 0 || $reportMarks > $maxReport)) {
    set_flash('error', "Report & Documentation marks must be between 0 and {$maxReport}.");
    header('Location: dashboard.php');
    exit;
}

// Validate individual student ranges
foreach ($studentMarksInput as $sId => $vals) {
    $pres = isset($vals['presentation']) && $vals['presentation'] !== '' ? (float)$vals['presentation'] : null;
    $qa   = isset($vals['qa']) && $vals['qa'] !== '' ? (float)$vals['qa'] : null;

    if ($pres !== null && ($pres < 0 || $pres > $maxPres)) {
        set_flash('error', "Presentation marks must be between 0 and {$maxPres}.");
        header('Location: dashboard.php');
        exit;
    }
    if ($qa !== null && ($qa < 0 || $qa > $maxQa)) {
        set_flash('error', "Viva Voce & Q&A marks must be between 0 and {$maxQa}.");
        header('Location: dashboard.php');
        exit;
    }
}

// Check existing marks for audit tracking
$wasFinalized = (int)($project['is_finalized'] ?? 0) === 1;

$existingSmStmt = $pdo->prepare("SELECT student_id, presentation_marks, qa_marks FROM student_marks WHERE project_id = ?");
$existingSmStmt->execute([$projectId]);
$existingStudentMarks = [];
while ($row = $existingSmStmt->fetch()) {
    $existingStudentMarks[$row['student_id']] = $row;
}

// Check if any mark changed
$hasChanges = false;
if ($project['old_report_marks'] != $reportMarks) {
    $hasChanges = true;
}
foreach ($studentMarksInput as $sId => $vals) {
    $sId = (int)$sId;
    $pres = isset($vals['presentation']) && $vals['presentation'] !== '' ? (float)$vals['presentation'] : null;
    $qa   = isset($vals['qa']) && $vals['qa'] !== '' ? (float)$vals['qa'] : null;

    $oldPres = isset($existingStudentMarks[$sId]['presentation_marks']) ? (float)$existingStudentMarks[$sId]['presentation_marks'] : null;
    $oldQa   = isset($existingStudentMarks[$sId]['qa_marks']) ? (float)$existingStudentMarks[$sId]['qa_marks'] : null;

    if ($pres != $oldPres || $qa != $oldQa) {
        $hasChanges = true;
        break;
    }
}

// If marks were already finalized and changed, enforce justification
if ($wasFinalized && $hasChanges && empty($justificationReason)) {
    set_flash('error', 'Mandatory justification reason is required when modifying finalized CIE marks.');
    header('Location: dashboard.php');
    exit;
}

$pdo->beginTransaction();
try {
    $auditStmt = $pdo->prepare("
        INSERT INTO marks_changes (project_id, student_id, field_name, old_value, new_value, changed_by, reason)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    // 1. Audit & Save Project Report Mark
    if ($project['old_report_marks'] != $reportMarks) {
        if ($wasFinalized) {
            $auditStmt->execute([
                $projectId,
                null,
                'report_marks',
                (string)$project['old_report_marks'],
                (string)$reportMarks,
                $user['id'],
                $justificationReason ?: 'Mark adjustment'
            ]);
        }
    }

    $finalizedAt = ($finalizeAction == 1) ? date('Y-m-d H:i:s') : ($wasFinalized ? $project['finalized_at'] : null);
    $finalizedBy = ($finalizeAction == 1) ? $user['id'] : ($wasFinalized ? $project['finalized_by'] : null);

    $savePmStmt = $pdo->prepare("
        INSERT INTO project_marks (project_id, report_marks, is_finalized, finalized_by, finalized_at, updated_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            report_marks = VALUES(report_marks),
            is_finalized = VALUES(is_finalized),
            finalized_by = VALUES(finalized_by),
            finalized_at = VALUES(finalized_at),
            updated_by = VALUES(updated_by)
    ");
    $savePmStmt->execute([$projectId, $reportMarks, $finalizeAction, $finalizedBy, $finalizedAt, $user['id']]);

    // 2. Audit & Save Student Marks
    $saveSmStmt = $pdo->prepare("
        INSERT INTO student_marks (project_id, student_id, presentation_marks, qa_marks, updated_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            presentation_marks = VALUES(presentation_marks),
            qa_marks = VALUES(qa_marks),
            updated_by = VALUES(updated_by)
    ");

    foreach ($studentMarksInput as $sId => $vals) {
        $sId = (int)$sId;
        $pres = isset($vals['presentation']) && $vals['presentation'] !== '' ? (float)$vals['presentation'] : null;
        $qa   = isset($vals['qa']) && $vals['qa'] !== '' ? (float)$vals['qa'] : null;

        $oldPres = isset($existingStudentMarks[$sId]['presentation_marks']) ? (float)$existingStudentMarks[$sId]['presentation_marks'] : null;
        $oldQa   = isset($existingStudentMarks[$sId]['qa_marks']) ? (float)$existingStudentMarks[$sId]['qa_marks'] : null;

        if ($wasFinalized) {
            if ($pres != $oldPres) {
                $auditStmt->execute([
                    $projectId,
                    $sId,
                    'presentation_marks',
                    (string)$oldPres,
                    (string)$pres,
                    $user['id'],
                    $justificationReason ?: 'Score adjustment'
                ]);
            }
            if ($qa != $oldQa) {
                $auditStmt->execute([
                    $projectId,
                    $sId,
                    'qa_marks',
                    (string)$oldQa,
                    (string)$qa,
                    $user['id'],
                    $justificationReason ?: 'Score adjustment'
                ]);
            }
        }

        $saveSmStmt->execute([$projectId, $sId, $pres, $qa, $user['id']]);
    }

    $pdo->commit();
    $statusMsg = ($finalizeAction == 1) ? 'CIE Marks locked and finalized successfully!' : 'CIE Marks draft saved.';
    set_flash('success', $statusMsg);

} catch (\Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to save CIE marks: ' . $e->getMessage());
}

header('Location: dashboard.php');
exit;
