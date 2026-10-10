<?php
// hub.php - Classroom Selector & Enrollment Gateway
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_login();
$user = current_user();
$error = '';
$success = '';

// Handle student joining classroom via invite code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_classroom') {
    if (!is_student()) {
        $error = 'Only students can enroll in classrooms.';
    } else {
        $inviteCode = strtoupper(trim($_POST['invite_code'] ?? ''));
        if (empty($inviteCode)) {
            $error = 'Please enter a classroom invite code.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE UPPER(invite_code) = ? LIMIT 1");
            $stmt->execute([$inviteCode]);
            $classroom = $stmt->fetch();

            if (!$classroom) {
                $error = 'Invalid invite code. No classroom found with code: ' . htmlspecialchars($inviteCode);
            } else {
                // Check if already enrolled
                $checkStmt = $pdo->prepare("SELECT * FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
                $checkStmt->execute([$classroom['id'], $user['id']]);
                if ($checkStmt->fetch()) {
                    // Already enrolled, just switch active classroom
                    $_SESSION['active_classroom_id'] = $classroom['id'];
                    set_flash('success', 'Switched to classroom: ' . $classroom['name']);
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Enroll student
                    $enrollStmt = $pdo->prepare("INSERT INTO classroom_students (classroom_id, student_id) VALUES (?, ?)");
                    $enrollStmt->execute([$classroom['id'], $user['id']]);

                    $_SESSION['active_classroom_id'] = $classroom['id'];
                    set_flash('success', 'Successfully enrolled in ' . $classroom['name'] . ' (' . $classroom['institution'] . ')');
                    header('Location: dashboard.php');
                    exit;
                }
            }
        }
    }
}

// Handle switching active classroom
if (isset($_GET['select_classroom'])) {
    $cId = (int)$_GET['select_classroom'];
    if (is_teacher()) {
        $cStmt = $pdo->prepare("
            SELECT id FROM classrooms 
            WHERE id = ? AND (teacher_id = ? OR id IN (SELECT classroom_id FROM projects WHERE mentor_id = ?))
        ");
        $cStmt->execute([$cId, $user['id'], $user['id']]);
    } else {
        $cStmt = $pdo->prepare("SELECT classroom_id FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
        $cStmt->execute([$cId, $user['id']]);
    }
    if ($cStmt->fetch()) {
        $_SESSION['active_classroom_id'] = $cId;
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch classrooms list
$classrooms = [];
if (is_teacher()) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name as coordinator_name,
               (CASE WHEN c.teacher_id = ? THEN 1 ELSE 0 END) as is_coordinator,
               (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id) as student_count,
               (SELECT COUNT(*) FROM projects p WHERE p.classroom_id = c.id) as project_count,
               (SELECT COUNT(*) FROM projects p WHERE p.classroom_id = c.id AND p.mentor_id = ?) as my_mentored_count
        FROM classrooms c 
        JOIN users u ON c.teacher_id = u.id
        WHERE c.teacher_id = ? 
           OR c.id IN (SELECT DISTINCT classroom_id FROM projects WHERE mentor_id = ?)
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
    $classrooms = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as teacher_name,
               (SELECT p.id FROM projects p JOIN project_members pm ON p.id = pm.project_id WHERE p.classroom_id = c.id AND pm.student_id = ? LIMIT 1) as my_project_id
        FROM classrooms c
        JOIN classroom_students cs ON c.id = cs.classroom_id
        JOIN users u ON c.teacher_id = u.id
        WHERE cs.student_id = ?
        ORDER BY cs.enrolled_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $classrooms = $stmt->fetchAll();
}

$pageTitle = 'Classrooms - PMS';
include __DIR__ . '/views/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Classrooms & Academic Cohorts</h1>
            <p class="text-sm text-slate-500 mt-1">Multi-institution partitioned project sections and review cohorts.</p>
        </div>
        <div>
            <?php if (is_teacher()): ?>
                <a href="create_classroom.php" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create New Classroom
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl flex items-center space-x-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (is_student()): ?>
        <!-- Student Join Classroom Banner -->
        <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-md">
            <div class="max-w-xl">
                <span class="inline-block px-3 py-1 bg-indigo-500/30 border border-indigo-400/30 text-indigo-200 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                    Enrollment Gateway
                </span>
                <h2 class="text-xl sm:text-2xl font-bold mb-2">Join Classroom with Invite Code</h2>
                <p class="text-sm text-indigo-200 mb-6">Enter the 6-character unique code shared by your project coordinator or faculty guide (e.g. <span class="font-mono bg-indigo-800/80 px-2 py-0.5 rounded text-white font-bold">RV-CS6B</span>).</p>
                
                <form action="hub.php" method="POST" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="action" value="join_classroom">
                    <input type="text" name="invite_code" placeholder="Enter Invite Code (e.g. RV-CS6B)" required class="flex-grow px-4 py-3 bg-white text-slate-800 font-mono font-bold text-sm tracking-widest uppercase rounded-xl border border-transparent focus:outline-none focus:ring-2 focus:ring-white">
                    <button type="submit" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm rounded-xl transition-colors shadow-sm whitespace-nowrap">
                        Join Classroom
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Classrooms List Grid -->
    <?php if (empty($classrooms)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">No Classrooms Found</h3>
            <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">
                <?= is_teacher() ? "You haven't created any project classrooms yet. Create your first classroom section to generate an invite code." : "You haven't joined any classrooms yet. Enter your coordinator's invite code above to get started." ?>
            </p>
            <?php if (is_teacher()): ?>
                <a href="create_classroom.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">
                    Create First Classroom
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($classrooms as $c): ?>
                <?php 
                $isActive = ($activeClassroomId == $c['id']);
                ?>
                <div class="bg-white rounded-2xl border <?= $isActive ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-200' ?> p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-mono font-bold rounded-lg uppercase tracking-wide border border-slate-200">
                                Code: <?= htmlspecialchars($c['invite_code']) ?>
                            </span>
                            <?php if ($isActive): ?>
                                <span class="inline-flex items-center text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Active Workspace
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 leading-snug mb-1">
                            <?= htmlspecialchars($c['name']) ?>
                        </h3>

                        <p class="text-xs font-semibold text-indigo-700 mb-1">
                            <?= htmlspecialchars($c['institution']) ?>
                        </p>
                        <p class="text-xs text-slate-500 mb-4">
                            <?= htmlspecialchars($c['department']) ?>
                        </p>

                        <div class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs text-slate-600 mb-4">
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-semibold">Semester Term</span>
                                <span class="font-medium"><?= date('M Y', strtotime($c['start_date'])) ?> - <?= date('M Y', strtotime($c['end_date'])) ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-semibold"><?= is_teacher() ? 'Enrollment' : 'Coordinator' ?></span>
                                <span class="font-medium">
                                    <?= is_teacher() ? ((int)$c['student_count'] . ' Students &bull; ' . (int)$c['project_count'] . ' Teams') : htmlspecialchars($c['teacher_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="flex items-center gap-2">
                            <a href="hub.php?select_classroom=<?= $c['id'] ?>" class="flex-grow text-center py-2 px-3 <?= $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' ?> rounded-xl text-xs font-semibold transition-colors">
                                <?= $isActive ? 'Open Dashboard &rarr;' : 'Switch to this Classroom' ?>
                            </a>
                            <?php if (is_student() && empty($c['my_project_id'])): ?>
                                <a href="create_project.php?classroom_id=<?= $c['id'] ?>" title="Form Team" class="py-2 px-3 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 rounded-xl text-xs font-semibold transition-colors whitespace-nowrap">
                                    + Form Team
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>
