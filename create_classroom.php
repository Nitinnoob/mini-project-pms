<?php
// create_classroom.php - Teacher Creates Isolated Classroom Section
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_teacher();
$user = current_user();
$error = '';

/**
 * Generate a clash-proof 6-character alphanumeric code
 */
function generateInviteCode(PDO $pdo): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid confusing chars like O, 0, 1, I
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare("SELECT id FROM classrooms WHERE invite_code = ? LIMIT 1");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $customCode = strtoupper(trim($_POST['custom_code'] ?? ''));

    if (empty($name) || empty($institution) || empty($department) || empty($startDate) || empty($endDate)) {
        $error = 'All fields are mandatory.';
    } elseif ($startDate >= $endDate) {
        $error = 'Semester end date must be after the start date.';
    } else {
        $inviteCode = '';
        if (!empty($customCode)) {
            // Validate custom code
            if (strlen($customCode) < 4 || strlen($customCode) > 10 || !preg_match('/^[A-Z0-9\-]+$/', $customCode)) {
                $error = 'Custom code must be 4-10 alphanumeric characters (hyphens allowed).';
            } else {
                $check = $pdo->prepare("SELECT id FROM classrooms WHERE invite_code = ?");
                $check->execute([$customCode]);
                if ($check->fetch()) {
                    $error = 'The invite code ' . htmlspecialchars($customCode) . ' is already taken.';
                } else {
                    $inviteCode = $customCode;
                }
            }
        }

        if (empty($error)) {
            if (empty($inviteCode)) {
                $inviteCode = generateInviteCode($pdo);
            }

            $stmt = $pdo->prepare("
                INSERT INTO classrooms (name, institution, department, teacher_id, invite_code, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $institution, $department, $user['id'], $inviteCode, $startDate, $endDate]);

            $newClassroomId = $pdo->lastInsertId();
            $_SESSION['active_classroom_id'] = $newClassroomId;

            set_flash('success', "Classroom '{$name}' created successfully with invite code {$inviteCode}!");
            header('Location: dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Create Classroom - PMS';
include __DIR__ . '/views/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="hub.php" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 mb-2">
            &larr; Back to Classrooms
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create Classroom Section</h1>
        <p class="text-sm text-slate-500 mt-1">Multi-institution isolated workspace for continuous evaluation.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl flex items-center space-x-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form action="create_classroom.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Classroom Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required placeholder="e.g. 6th Sem Mini Project (Sec A)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Institution / College Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="institution" required placeholder="e.g. RV College of Engineering" value="<?= htmlspecialchars($_POST['institution'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Appears on official printable ledgers.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Department <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="department" required placeholder="e.g. Computer Science & Engineering" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Semester Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" required value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-02-01')) ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Drives Saturday review schedule mapping.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Semester End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" required value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-05-31')) ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Custom Invite Code <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                </label>
                <input type="text" name="custom_code" placeholder="Leave blank to auto-generate (e.g. RV-CS6B)" value="<?= htmlspecialchars($_POST['custom_code'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl font-mono uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-[11px] text-slate-400 mt-1">Students will use this code on their dashboard to enroll.</p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="hub.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md transition-colors">
                    Create & Activate Classroom
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>
