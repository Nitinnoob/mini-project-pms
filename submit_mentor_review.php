<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';
require_once 'notify.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $classroom_id = $_POST['classroom_id'] ?? null;
    $week_number = $_POST['week_number'] ?? null;
    $status = $_POST['status'] ?? 'pending';
    $mentor_remarks = $_POST['mentor_remarks'] ?? '';
    $attendance = $_POST['attendance'] ?? []; // Array of user_id => 1 if checked
    
    if (!$project_id || !$week_number || !$classroom_id) {
        header("Location: dashboard.php");
        exit;
    }
    
    // Verify project belongs to classroom AND user is authorized (Coordinator or Assigned Mentor)
    $stmtAuth = $pdo->prepare("
        SELECT p.mentor_id, c.created_by 
        FROM projects p
        JOIN classrooms c ON p.classroom_id = c.id
        WHERE p.id = ? AND p.classroom_id = ?
    ");
    $stmtAuth->execute([$project_id, $classroom_id]);
    $projectAuth = $stmtAuth->fetch(PDO::FETCH_ASSOC);
    
    if ($projectAuth) {
        $isCoordinator = ((int)$projectAuth['created_by'] === (int)$_SESSION['user_id']);
        $isAssignedMentor = ((int)$projectAuth['mentor_id'] === (int)$_SESSION['user_id']);
        
        if ($isCoordinator || $isAssignedMentor) {
        try {
            $pdo->beginTransaction();
            
            // Ensure a weekly_submission row exists even if the students didn't upload files
            // Mentors should be able to flag a week as missed or attended without files.
            $stmtSub = $pdo->prepare("
                INSERT IGNORE INTO weekly_submissions (project_id, week_number) 
                VALUES (?, ?)
            ");
            $stmtSub->execute([$project_id, $week_number]);
            
            // Get the submission ID
            $stmtGet = $pdo->prepare("SELECT id FROM weekly_submissions WHERE project_id = ? AND week_number = ?");
            $stmtGet->execute([$project_id, $week_number]);
            $submission_id = $stmtGet->fetchColumn();
            
            // Insert or Update the review
            $stmtRev = $pdo->prepare("
                INSERT INTO weekly_reviews (submission_id, reviewed_by, status, mentor_remarks) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                reviewed_by = VALUES(reviewed_by), status = VALUES(status), mentor_remarks = VALUES(mentor_remarks)
            ");
            $stmtRev->execute([$submission_id, $_SESSION['user_id'], $status, $mentor_remarks]);
            
            // Update attendance
            // First, delete existing attendance for this submission to replace cleanly
            $stmtDelAtt = $pdo->prepare("DELETE FROM weekly_attendance WHERE submission_id = ?");
            $stmtDelAtt->execute([$submission_id]);
            
            // Fetch the roster to know everyone who *should* be in the attendance
            $stmtRoster = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND join_status = 'Active'");
            $stmtRoster->execute([$project_id]);
            $rosterIds = $stmtRoster->fetchAll(PDO::FETCH_COLUMN);
            
            $stmtInsAtt = $pdo->prepare("INSERT INTO weekly_attendance (submission_id, user_id, present) VALUES (?, ?, ?)");
            foreach ($rosterIds as $uid) {
                $isPresent = isset($attendance[$uid]) ? 1 : 0;
                $stmtInsAtt->execute([$submission_id, $uid, $isPresent]);
            }
            
            $pdo->commit();

            $statusText = ['approved' => 'approved', 'revision_needed' => 'sent back for revision', 'pending' => 'updated'][$status] ?? 'updated';
            notify_project_members($pdo, $project_id, 'review',
                'Your mentor ' . $statusText . ' the Week ' . (int)$week_number . ' log' . (trim($mentor_remarks) !== '' ? ' and left remarks.' : '.'),
                'dashboard.php?classroom_id=' . urlencode($classroom_id), $_SESSION['user_id']);
        } catch (Exception $e) {
            $pdo->rollBack();
            // log error
        }
    }
    
    // Redirect back to the drilldown view, and re-open the logs tab using a hash
    // The hash could be used in JS, but for now just taking them to the dashboard is fine
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&project_id=" . urlencode($project_id));
    exit;
}
