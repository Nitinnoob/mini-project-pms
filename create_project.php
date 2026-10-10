<?php
// create_project.php - Student Creates Project Team & Selects Faculty Guide
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/meeting_engine.php';

require_student();
$user = current_user();
$error = '';

$classroomId = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : ($_SESSION['active_classroom_id'] ?? null);

if (!$classroomId) {
    set_flash('error', 'Please select or join a classroom first.');
    header('Location: hub.php');
    exit;
}

// Verify student enrollment in this classroom
$cStmt = $pdo->prepare("
    SELECT c.* FROM classrooms c
    JOIN classroom_students cs ON c.id = cs.classroom_id
    WHERE c.id = ? AND cs.student_id = ?
");
$cStmt->execute([$classroomId, $user['id']]);
$classroom = $cStmt->fetch();

if (!$classroom) {
    set_flash('error', 'You are not enrolled in this classroom section.');
    header('Location: hub.php');
    exit;
}

// Check if student already belongs to a project in this classroom
$checkExisting = $pdo->prepare("
    SELECT p.id, p.title 
    FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    WHERE p.classroom_id = ? AND pm.student_id = ?
");
$checkExisting->execute([$classroomId, $user['id']]);
if ($existing = $checkExisting->fetch()) {
    set_flash('error', 'You are already registered with project: "' . $existing['title'] . '" in this classroom.');
    header('Location: dashboard.php');
    exit;
}

// Fetch available faculty guides (teachers)
$tStmt = $pdo->query("SELECT id, name, identifier FROM users WHERE role = 'teacher' ORDER BY name ASC");
$teachers = $tStmt->fetchAll();

// Fetch eligible classroom peers (students in this classroom who do not belong to any project yet)
$pStmt = $pdo->prepare("
    SELECT u.id, u.name, u.identifier
    FROM users u
    JOIN classroom_students cs ON u.id = cs.student_id
    WHERE cs.classroom_id = ? 
      AND u.id != ?
      AND u.id NOT IN (
          SELECT pm.student_id 
          FROM project_members pm 
          JOIN projects p ON pm.project_id = p.id 
          WHERE p.classroom_id = ?
      )
    ORDER BY u.name ASC
");
$pStmt->execute([$classroomId, $user['id'], $classroomId]);
$availablePeers = $pStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $mentorId = (int)($_POST['mentor_id'] ?? 0);
    $selectedMembers = $_POST['members'] ?? [];

    if (empty($title)) {
        $error = 'Project title is required.';
    } elseif ($mentorId <= 0) {
        $error = 'Please assign a faculty guide / mentor.';
    } elseif (count($selectedMembers) > 3) {
        $error = 'Maximum team size is 4 (1 leader + up to 3 batchmates).';
    } else {
        // Verify mentor is a real teacher
        $mCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
        $mCheck->execute([$mentorId]);
        if (!$mCheck->fetch()) {
            $error = 'Selected faculty mentor is invalid.';
        } else {
            // Create Project
            $pdo->beginTransaction();
            try {
                $pInsert = $pdo->prepare("
                    INSERT INTO projects (classroom_id, title, description, created_by, mentor_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $pInsert->execute([$classroomId, $title, $description, $user['id'], $mentorId]);
                $projectId = $pdo->lastInsertId();

                // Add Leader
                $mInsert = $pdo->prepare("INSERT INTO project_members (project_id, student_id, is_leader) VALUES (?, ?, 1)");
                $mInsert->execute([$projectId, $user['id']]);

                // Add Teammates
                $tmInsert = $pdo->prepare("INSERT INTO project_members (project_id, student_id, is_leader) VALUES (?, ?, 0)");
                foreach ($selectedMembers as $memId) {
                    $memId = (int)$memId;
                    if ($memId > 0 && $memId !== $user['id']) {
                        $tmInsert->execute([$projectId, $memId]);
                    }
                }

                // Derive Saturday Review Meetings
                sync_project_meetings($pdo, $projectId);

                // Initialize Project Marks container
                $pmInsert = $pdo->prepare("INSERT INTO project_marks (project_id) VALUES (?)");
                $pmInsert->execute([$projectId]);

                // Initialize Student Marks container for each member
                $allMembers = array_merge([$user['id']], array_map('intval', $selectedMembers));
                $smInsert = $pdo->prepare("INSERT INTO student_marks (project_id, student_id) VALUES (?, ?)");
                foreach ($allMembers as $sId) {
                    if ($sId > 0) {
                        $smInsert->execute([$projectId, $sId]);
                    }
                }

                $pdo->commit();

                $_SESSION['active_classroom_id'] = $classroomId;
                set_flash('success', "Project team '{$title}' created and review schedule derived!");
                header('Location: dashboard.php');
                exit;

            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to create project: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Form Project Team - PMS';
include __DIR__ . '/views/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="dashboard.php" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 mb-2">
            &larr; Back to Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Project Team</h1>
        <p class="text-sm text-slate-500 mt-1">
            Section: <strong class="text-slate-800"><?= htmlspecialchars($classroom['name']) ?></strong> 
            (<?= htmlspecialchars($classroom['institution']) ?>)
        </p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl flex items-center space-x-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form action="create_project.php?classroom_id=<?= $classroomId ?>" method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Project Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" required placeholder="e.g. Smart IoT Traffic Congestion Controller" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Project Abstract / Scope
                </label>
                <textarea name="description" rows="3" placeholder="Provide a concise technical abstract describing proposed system architecture, hardware/software stack, and target outcomes." class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Assigned Project Guide / Mentor <span class="text-red-500">*</span>
                </label>
                <select name="mentor_id" required class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Select Faculty Guide --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ((int)($_POST['mentor_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?> (Staff ID: <?= htmlspecialchars($t['identifier']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">This faculty member will conduct Saturday reviews, mark attendance, and award CIE marks.</p>
            </div>

            <!-- Team Composition -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Team Composition (Max 4 Members)</h3>
                        <p class="text-xs text-slate-500">You are the Team Leader (1 of 4). Select up to 3 batchmates from this classroom section.</p>
                    </div>
                    <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-2.5 py-1 rounded-full border border-indigo-200">
                        Leader: <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['identifier']) ?>)
                    </span>
                </div>

                <?php if (empty($availablePeers)): ?>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                        No unassigned peers found in this classroom section. You can proceed as a solo project team or ask teammates to register and join using classroom invite code <strong><?= htmlspecialchars($classroom['invite_code']) ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="space-y-2 max-h-48 overflow-y-auto p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <?php foreach ($availablePeers as $peer): ?>
                            <label class="flex items-center space-x-3 p-2 bg-white rounded-lg border border-slate-200 hover:border-indigo-400 cursor-pointer text-xs">
                                <input type="checkbox" name="members[]" value="<?= $peer['id'] ?>" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                <div class="flex-grow">
                                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($peer['name']) ?></span>
                                    <span class="text-slate-500 font-mono ml-2">(<?= htmlspecialchars($peer['identifier']) ?>)</span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="dashboard.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md transition-colors">
                    Register Project Team
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>
