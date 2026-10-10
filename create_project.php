<?php
// create_project.php - A student starts a team with a project idea and picks a faculty guide
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_student();
$user = current_user();
$error = '';

$classroomId = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : ($_SESSION['active_classroom_id'] ?? null);
if (!$classroomId) {
    set_flash('error', 'Please select or join a classroom first.');
    header('Location: hub.php');
    exit;
}

// The student must be enrolled in this classroom
$stmt = $pdo->prepare("
    SELECT c.* FROM classrooms c
    JOIN classroom_students cs ON c.id = cs.classroom_id
    WHERE c.id = ? AND cs.student_id = ?
");
$stmt->execute([$classroomId, $user['id']]);
$classroom = $stmt->fetch();
if (!$classroom) {
    set_flash('error', 'You are not enrolled in this classroom.');
    header('Location: hub.php');
    exit;
}

// One team per student per classroom
$stmt = $pdo->prepare("
    SELECT 1 FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    WHERE p.classroom_id = ? AND pm.student_id = ?
");
$stmt->execute([$classroomId, $user['id']]);
if ($stmt->fetch()) {
    $_SESSION['active_classroom_id'] = $classroomId;
    set_flash('error', 'You are already in a team in this classroom.');
    header('Location: dashboard.php');
    exit;
}

$teachers = $pdo->query("SELECT id, name, identifier FROM users WHERE role = 'teacher' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $mentorId    = (int)($_POST['mentor_id'] ?? 0);

    if ($title === '') {
        $error = 'Project title is required.';
    } elseif (!in_array($mentorId, array_map('intval', array_column($teachers, 'id')), true)) {
        $error = 'Please choose a faculty guide.';
    } else {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO projects (classroom_id, title, description, created_by, mentor_id) VALUES (?, ?, ?, ?, ?)")
                ->execute([$classroomId, $title, $description, $user['id'], $mentorId]);
            $projectId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO project_members (project_id, student_id, is_leader) VALUES (?, ?, 1)")->execute([$projectId, $user['id']]);
            $pdo->prepare("INSERT INTO project_marks (project_id) VALUES (?)")->execute([$projectId]);
            $pdo->prepare("INSERT INTO student_marks (project_id, student_id) VALUES (?, ?)")->execute([$projectId, $user['id']]);

            $pdo->commit();

            $_SESSION['active_classroom_id'] = $classroomId;
            set_flash('success', 'Team started. Classmates can now ask to join it.');
            header('Location: dashboard.php');
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error = 'Could not start the team: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Start a Team - PMS';
include __DIR__ . '/views/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">
    <div>
        <a href="dashboard.php" class="text-sm text-teal-700 hover:underline">&larr; Back</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Start a team</h1>
        <p class="muted"><?= h($classroom['name']) ?>, <?= h($classroom['institution']) ?>. You become the team leader and classmates can ask to join you.</p>
    </div>

    <?php if ($error): ?>
        <div class="px-4 py-2.5 border rounded-md text-sm bg-red-50 border-red-200 text-red-800"><?= h($error) ?></div>
    <?php endif; ?>

    <form action="create_project.php?classroom_id=<?= (int)$classroomId ?>" method="POST" class="panel p-6 space-y-4">
        <div>
            <label class="label">Project title</label>
            <input type="text" name="title" required value="<?= h($_POST['title'] ?? '') ?>" placeholder="e.g. Smart traffic congestion controller" class="field">
        </div>
        <div>
            <label class="label">Abstract</label>
            <textarea name="description" rows="4" placeholder="What will you build, with which tools, and what should it achieve?" class="field"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="label">Faculty guide</label>
            <select name="mentor_id" required class="field">
                <option value="">Choose a guide</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ((int)($_POST['mentor_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>><?= h($t['name']) ?> (<?= h($t['identifier']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <p class="muted mt-1">Your guide approves your idea, runs the Saturday reviews and awards marks.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">
            <a href="dashboard.php" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">Start team</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>
