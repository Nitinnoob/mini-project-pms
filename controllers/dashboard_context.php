<?php
// Resolves classroom, contextual role, active view and header metadata. Sets $viewData.
$viewData = [];
$viewData['isCoordinator'] = false;
$viewData['isTeacherDrilldown'] = false;
$viewData['isLeaderView'] = false; // Phase 4: gates the weekly-log submission form
$viewData['classroomStartDate'] = null;
$viewData['classroomEndDate'] = null;
$viewData['weeklyLogs'] = [];

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$viewData['classroom_id'] = $_GET['classroom_id'] ?? null;

    if (!$viewData['classroom_id']) {
        header("Location: hub.php");
        exit;
    }


    // Fetch classroom details
    $stmtClass = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
    $stmtClass->execute([$viewData['classroom_id']]);
    $actualClassroom = $stmtClass->fetch(PDO::FETCH_ASSOC);
    if (!$actualClassroom) { header("Location: hub.php"); exit; }
    $viewData['isCoordinator'] = ($actualClassroom['created_by'] == $_SESSION['user_id']);
    $viewData['classroomName'] = $actualClassroom['name'] ?? 'Classroom';
    $viewData['inviteCode'] = $actualClassroom['invite_code'] ?? 'XXXXXX';
    $viewData['maxTeamSize'] = $actualClassroom['max_team_size'] ?? 10;
    $viewData['classroomStartDate'] = $actualClassroom['start_date'] ?? null;
    $viewData['classroomEndDate'] = $actualClassroom['end_date'] ?? null;
    // Fetch the user's role for THIS specific classroom
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // User is not part of this classroom
        header("Location: hub.php");
        exit;
    }

    $contextualRole = $member['role'] ?? 'Team Member'; // Admin or Team Member

    if ($contextualRole === 'Admin') {
        if (!empty($_GET['project_id'])) {
            // Teacher Drilldown Mode: Pretend to be the Project Leader, but disable writes
            $viewData['actualView'] = 'Project Leader';
            $viewData['isTeacherDrilldown'] = true;
            $viewData['myProjectId'] = (int)$_GET['project_id'];
            
            // Fetch the project details
            $stmtProj = $pdo->prepare("SELECT id, name, description, mentor_id FROM projects WHERE id = ? AND classroom_id = ?");
            $stmtProj->execute([$viewData['myProjectId'], $viewData['classroom_id']]);
            $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);
            
            if (!$viewData['myProject']) { header("Location: dashboard.php?classroom_id=" . urlencode($viewData['classroom_id'])); exit; }
            
            // Fetch roster
            $stmtRoster = $pdo->prepare("SELECT u.id, u.username, pm.is_leader FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? AND pm.join_status = 'Active'");
            $stmtRoster->execute([$viewData['myProjectId']]);
            $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
            
            $viewData['pendingRequests'] = []; // Teachers don't manage team invites
            $viewData['unassignedClassmates'] = [];
        } else {
            $viewData['actualView'] = 'Teacher';
        }
    } else {
        // Check if the user is part of an active project in this classroom
        $stmtProj = $pdo->prepare("
            SELECT p.id, p.name, pm.is_leader, pm.join_status 
            FROM projects p 
            JOIN project_members pm ON p.id = pm.project_id 
            WHERE p.classroom_id = ? AND pm.user_id = ?
        ");
        $stmtProj->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
        $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);

        if ($viewData['myProject'] && $viewData['myProject']['join_status'] === 'Active') {
            $viewData['actualView'] = ($viewData['myProject']['is_leader'] == 1) ? 'Project Leader' : 'Student';
            $viewData['myProjectId'] = $viewData['myProject']['id'];
            
            if ($viewData['actualView'] === 'Project Leader') {
                $viewData['isLeaderView'] = true;
                $stmtPending = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Pending'
                ");
                $stmtPending->execute([$viewData['myProjectId']]);
                $viewData['pendingRequests'] = $stmtPending->fetchAll(PDO::FETCH_ASSOC);
                
                $stmtRoster = $pdo->prepare("
                    SELECT u.id, u.username, pm.is_leader
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Active'
                ");
                $stmtRoster->execute([$viewData['myProjectId']]);
                $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
                
                // Fetch unassigned classmates to invite
                $stmtClassmates = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM classroom_members cm
                    JOIN users u ON cm.user_id = u.id
                    WHERE cm.classroom_id = ? AND cm.role != 'Admin'
                    AND u.id NOT IN (
                        SELECT user_id FROM project_members WHERE project_id = ?
                    )
                ");
                $stmtClassmates->execute([$viewData['classroom_id'], $viewData['myProjectId']]);
                $viewData['unassignedClassmates'] = $stmtClassmates->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $viewData['actualView'] = 'Marketplace';
            
            // Fetch all projects in this classroom for the marketplace
            $stmtAllProjs = $pdo->prepare("
                SELECT p.id, p.name, p.description,
                       (SELECT COUNT(*) FROM project_members WHERE project_id = p.id AND join_status = 'Active') as active_members,
                       (SELECT join_status FROM project_members WHERE project_id = p.id AND user_id = ?) as my_status
                FROM projects p
                WHERE p.classroom_id = ?
            ");
            $stmtAllProjs->execute([$_SESSION['user_id'], $viewData['classroom_id']]);
            $viewData['availableProjects'] = $stmtAllProjs->fetchAll(PDO::FETCH_ASSOC);
        }
    }
$modeSlug = ['Student' => 'student', 'Project Leader' => 'leader', 'Teacher' => 'teacher', 'Marketplace' => 'student'][$viewData['actualView']];
if (!empty($viewData['isTeacherDrilldown'])) {
    $modeSlug = 'teacher';
}

$headerTitle = "PMS";
if ($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') {
    $headerTitle = htmlspecialchars($viewData['myProject']['name'] ?? 'My Project');
} elseif ($viewData['actualView'] === 'Marketplace') {
    $headerTitle = "Available Projects";
} elseif ($viewData['actualView'] === 'Teacher') {
    $headerTitle = "Classroom Overview";
}
