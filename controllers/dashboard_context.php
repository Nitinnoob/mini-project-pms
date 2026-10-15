<?php
// Resolves classroom, contextual role, active view and header metadata. Sets $viewData.
$viewData = [];
$viewData['isCoordinator'] = false;
$viewData['isTeacherDrilldown'] = false;
$viewData['isLeaderView'] = false;
$viewData['meetings'] = [];

$rawUsername = $_SESSION['username'] ?? 'User';
$username = htmlspecialchars($rawUsername, ENT_QUOTES, 'UTF-8');
$initial = strtoupper(mb_substr($rawUsername, 0, 1));
$viewData['classroom_id'] = $_GET['classroom_id'] ?? null;

if (!$viewData['classroom_id']) {
    header("Location: hub.php");
    exit;
}

// Fetch classroom details
$stmtClass = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmtClass->execute([$viewData['classroom_id']]);
$actualClassroom = $stmtClass->fetch(PDO::FETCH_ASSOC);
if (!$actualClassroom) {
    header("Location: hub.php");
    exit;
}

$viewData['isCoordinator'] = ($actualClassroom['created_by'] == $_SESSION['user_id']);
$viewData['classroomName'] = $actualClassroom['name'] ?? 'Classroom';
$viewData['inviteCode'] = $actualClassroom['invite_code'] ?? 'XXXXXX';
$viewData['maxTeamSize'] = min(4, max(1, (int)($actualClassroom['max_team_size'] ?? 4)));

// Fetch the user's role for THIS specific classroom
$stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
$stmt->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header("Location: hub.php");
    exit;
}

$contextualRole = $member['role'] ?? 'Team Member';
$viewData['classroomRole'] = $contextualRole;

if ($contextualRole === 'Admin') {
    if (!empty($_GET['project_id'])) {
        // Teacher Drilldown Mode
        $viewData['actualView'] = 'Student';
        $viewData['isTeacherDrilldown'] = true;
        $viewData['isLeaderView'] = false;
        $viewData['myProjectId'] = (int)$_GET['project_id'];

        $stmtProj = $pdo->prepare("SELECT id, name, description, mentor_id, github_url FROM projects WHERE id = ? AND classroom_id = ?");
        $stmtProj->execute([$viewData['myProjectId'], $viewData['classroom_id']]);
        $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);

        if (!$viewData['myProject']) {
            header("Location: dashboard.php?classroom_id=" . urlencode((string)$viewData['classroom_id']));
            exit;
        }

        $stmtRoster = $pdo->prepare("
            SELECT u.id, u.username, pm.is_leader
            FROM project_members pm
            JOIN users u ON pm.user_id = u.id
            WHERE pm.project_id = ? AND pm.join_status = 'Active'
        ");
        $stmtRoster->execute([$viewData['myProjectId']]);
        $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);

        $viewData['pendingRequests'] = [];
        $viewData['unassignedClassmates'] = [];
    } else {
        $viewData['actualView'] = 'Teacher';
    }
} else {
    $stmtProj = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.github_url, pm.is_leader, pm.join_status
        FROM projects p
        JOIN project_members pm ON p.id = pm.project_id
        WHERE p.classroom_id = ? AND pm.user_id = ?
        ORDER BY (pm.join_status = 'Active') DESC
        LIMIT 1
    ");
    $stmtProj->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
    $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);

    if ($viewData['myProject'] && $viewData['myProject']['join_status'] === 'Active') {
        $viewData['actualView'] = 'Student';
        $viewData['myProjectId'] = (int)$viewData['myProject']['id'];
        $viewData['isLeaderView'] = ((int)$viewData['myProject']['is_leader'] === 1);

        $stmtRoster = $pdo->prepare("
            SELECT u.id, u.username, pm.is_leader
            FROM project_members pm
            JOIN users u ON pm.user_id = u.id
            WHERE pm.project_id = ? AND pm.join_status = 'Active'
        ");
        $stmtRoster->execute([$viewData['myProjectId']]);
        $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);

        if ($viewData['isLeaderView']) {
            $stmtPending = $pdo->prepare("
                SELECT u.id, u.username
                FROM project_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ? AND pm.join_status = 'Pending'
            ");
            $stmtPending->execute([$viewData['myProjectId']]);
            $viewData['pendingRequests'] = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

            $stmtClassmates = $pdo->prepare("
                SELECT u.id, u.username, cm.usn
                FROM classroom_members cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.classroom_id = ? AND cm.role != 'Admin'
                AND u.id NOT IN (
                    SELECT pm.user_id FROM project_members pm
                    JOIN projects p ON p.id = pm.project_id
                    WHERE p.classroom_id = ? AND pm.join_status = 'Active'
                )
            ");
            $stmtClassmates->execute([$viewData['classroom_id'], $viewData['classroom_id']]);
            $viewData['unassignedClassmates'] = $stmtClassmates->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $viewData['pendingRequests'] = [];
            $viewData['unassignedClassmates'] = [];
        }
    } else {
        $viewData['actualView'] = 'Marketplace';

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

$modeSlug = match ($viewData['actualView']) {
    'Teacher' => 'teacher',
    'Student' => (!empty($viewData['isTeacherDrilldown']) ? 'teacher' : ($viewData['isLeaderView'] ? 'leader' : 'student')),
    'Marketplace' => 'student',
    default => 'student',
};

$headerTitle = "PMS";
if ($viewData['actualView'] === 'Student') {
    $headerTitle = htmlspecialchars($viewData['myProject']['name'] ?? 'My Project', ENT_QUOTES, 'UTF-8');
} elseif ($viewData['actualView'] === 'Marketplace') {
    $headerTitle = "Available Projects";
} elseif ($viewData['actualView'] === 'Teacher') {
    $headerTitle = "Classroom Overview";
}
