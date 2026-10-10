<?php
// views/student.php - Student workspace: weekly logs, action items, uploads, attendance and CIE score
require_once __DIR__ . '/../dbs.php';
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../meeting_engine.php';

require_student();
$user = current_user();

// Use the active classroom, or fall back to the most recently joined one
$classroomId = $_SESSION['active_classroom_id'] ?? null;
if (!$classroomId) {
    $stmt = $pdo->prepare("SELECT classroom_id FROM classroom_students WHERE student_id = ? ORDER BY enrolled_at DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $classroomId = $_SESSION['active_classroom_id'] = $stmt->fetchColumn() ?: null;
}
if (!$classroomId) {
    header('Location: hub.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmt->execute([$classroomId]);
$classroom = $stmt->fetch();

// The student's project, with its guide and marks
$stmt = $pdo->prepare("
    SELECT p.*, mentor.name AS mentor_name, mentor.identifier AS mentor_code,
           pmarks.report_marks, pmarks.is_finalized,
           smarks.presentation_marks, smarks.qa_marks
    FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    JOIN users mentor ON p.mentor_id = mentor.id
    LEFT JOIN project_marks pmarks ON p.id = pmarks.project_id
    LEFT JOIN student_marks smarks ON (p.id = smarks.project_id AND smarks.student_id = ?)
    WHERE p.classroom_id = ? AND pm.student_id = ?
    LIMIT 1
");
$stmt->execute([$user['id'], $classroomId, $user['id']]);
$project = $stmt->fetch();

// No project yet: invite the student to form a team
if (!$project) {
    $pageTitle = 'Dashboard - PMS';
    include __DIR__ . '/header.php';
    ?>
    <div class="max-w-xl mx-auto px-4 py-16 text-center">
        <h2 class="text-xl font-bold">Form your project team</h2>
        <p class="mt-2 text-sm text-zinc-600">
            You are enrolled in <strong><?= h($classroom['name']) ?></strong> (<?= h($classroom['institution']) ?>).
            Register your project title, choose your faculty guide and add up to 3 teammates.
        </p>
        <a href="create_project.php?classroom_id=<?= (int)$classroom['id'] ?>" class="btn btn-primary mt-6">Create project team</a>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

$projectId = $project['id'];

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.identifier, pm.is_leader
    FROM project_members pm
    JOIN users u ON pm.student_id = u.id
    WHERE pm.project_id = ?
    ORDER BY pm.is_leader DESC, u.name ASC
");
$stmt->execute([$projectId]);
$teammates = $stmt->fetchAll();

$attendance = calculate_project_attendance($pdo, $projectId)[$user['id']]
    ?? ['percentage' => 100.0, 'has_shortage' => false, 'attended' => 0, 'total_held' => 0];

$stmt = $pdo->prepare("
    SELECT wm.*, submitter.name AS submitter_name
    FROM weekly_meetings wm
    LEFT JOIN users submitter ON wm.submitted_by = submitter.id
    WHERE wm.project_id = ?
    ORDER BY wm.week_number ASC
");
$stmt->execute([$projectId]);
$meetings = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT gi.*, wm.week_number
    FROM guide_instructions gi
    JOIN weekly_meetings wm ON gi.meeting_id = wm.id
    WHERE wm.project_id = ?
    ORDER BY gi.created_at DESC
");
$stmt->execute([$projectId]);
$directives = $stmt->fetchAll();
$pendingCount = count(array_filter($directives, fn($d) => $d['status'] !== 'done'));

$stmt = $pdo->prepare("SELECT * FROM project_assets WHERE project_id = ? ORDER BY is_spotlight DESC, id DESC");
$stmt->execute([$projectId]);
$assets = $stmt->fetchAll();

$totalCie = cie_total($project['report_marks'], $project['presentation_marks'], $project['qa_marks']);

$pageTitle = 'Student Portal - ' . $project['title'];
include __DIR__ . '/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8 space-y-8">

    <?php if ($attendance['has_shortage']): ?>
        <div class="border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">Attendance is below the required 75%</p>
            <p>You have attended <?= $attendance['attended'] ?> of <?= $attendance['total_held'] ?> reviews (<?= $attendance['percentage'] ?>%). Regular attendance is required to sit for the final viva.</p>
        </div>
    <?php endif; ?>

    <!-- Project summary -->
    <section class="flex flex-col lg:flex-row justify-between gap-6">
        <div class="max-w-3xl">
            <p class="text-sm text-zinc-500"><?= h($classroom['institution']) ?>, <?= h($classroom['department']) ?></p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight"><?= h($project['title']) ?></h1>
            <p class="mt-2 text-zinc-600"><?= h($project['description']) ?></p>

            <dl class="mt-4 space-y-1 text-sm">
                <div class="flex gap-2"><dt class="w-24 text-zinc-500">Faculty guide</dt><dd><?= h($project['mentor_name']) ?> (<?= h($project['mentor_code']) ?>)</dd></div>
                <div class="flex gap-2">
                    <dt class="w-24 shrink-0 text-zinc-500">Team</dt>
                    <dd><?= implode(', ', array_map(fn($t) => h($t['name']) . ' (' . h($t['identifier']) . ')' . ($t['is_leader'] ? ' ★' : ''), $teammates)) ?></dd>
                </div>
            </dl>
        </div>

        <div class="flex gap-3 lg:flex-col lg:w-52">
            <div class="panel flex-1 p-4">
                <p class="text-sm text-zinc-500">Attendance</p>
                <p class="text-2xl font-bold tabular-nums <?= $attendance['has_shortage'] ? 'text-red-600' : 'text-emerald-600' ?>"><?= $attendance['percentage'] ?>%</p>
                <p class="text-xs text-zinc-500"><?= $attendance['attended'] ?> of <?= $attendance['total_held'] ?> reviews</p>
            </div>
            <div class="panel flex-1 p-4">
                <p class="text-sm text-zinc-500">CIE score</p>
                <p class="text-2xl font-bold tabular-nums"><?= fmt_mark($totalCie) ?> <span class="text-sm font-normal text-zinc-500">/ 100</span></p>
                <p class="text-xs text-zinc-500"><?= $project['is_finalized'] ? 'Finalized by guide' : 'In progress' ?></p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Weekly reviews -->
        <section class="lg:col-span-2 space-y-4">
            <div>
                <h2 class="text-lg font-bold">Saturday reviews</h2>
                <p class="muted">Weekly progress logs and guide remarks.</p>
            </div>

            <?php foreach ($meetings as $m):
                $temp   = get_week_temporal_status($m['meeting_date'], $m['status']);
                $update = !empty($m['team_update']) ? json_decode($m['team_update'], true) : null;
            ?>
                <article class="panel p-5 <?= $temp['is_current'] ? '!border-teal-600 ring-1 ring-teal-600' : '' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">Week <?= $m['week_number'] ?></h3>
                            <p class="muted"><?= date('l, d M Y', strtotime($m['meeting_date'])) ?></p>
                        </div>
                        <?php if ($temp['is_future']): ?>
                            <span class="tag tag-mute">Opens <?= date('d M', strtotime($temp['date_from'])) ?></span>
                        <?php elseif ($temp['is_current']): ?>
                            <span class="tag tag-info">Open for submission</span>
                        <?php else: ?>
                            <span class="tag <?= $m['status'] === 'held' ? 'tag-ok' : 'tag-mute' ?>"><?= h(ucfirst($m['status'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($update): ?>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-zinc-500">Work done</dt><dd class="mt-0.5 whitespace-pre-line"><?= h($update['work_done'] ?? '') ?></dd></div>
                            <div><dt class="text-zinc-500">Next steps</dt><dd class="mt-0.5 whitespace-pre-line"><?= h($update['next_steps'] ?? '') ?></dd></div>
                            <?php if (!empty($update['blockers']) && $update['blockers'] !== 'None reported'): ?>
                                <div class="rounded-md bg-red-50 px-3 py-2 text-red-800"><dt class="text-red-600">Blockers</dt><dd class="mt-0.5"><?= h($update['blockers']) ?></dd></div>
                            <?php endif; ?>
                        </dl>
                        <p class="mt-3 text-xs text-zinc-400">Submitted by <?= h($m['submitter_name'] ?? 'Team') ?> on <?= date('d M Y, h:i A', strtotime($m['submitted_at'])) ?></p>

                    <?php elseif ($temp['student_can_edit']): ?>
                        <form action="submit_weekly_log.php" method="POST" class="mt-4 space-y-3">
                            <input type="hidden" name="meeting_id" value="<?= (int)$m['id'] ?>">
                            <div>
                                <label class="label">Work completed this week</label>
                                <textarea name="work_done" required rows="2" placeholder="Milestones reached, models trained, endpoints tested" class="field"></textarea>
                            </div>
                            <div>
                                <label class="label">Plan for next week</label>
                                <textarea name="next_steps" required rows="2" class="field"></textarea>
                            </div>
                            <div>
                                <label class="label">Blockers (optional)</label>
                                <input type="text" name="blockers" class="field">
                            </div>
                            <button type="submit" class="btn btn-primary">Submit log</button>
                        </form>

                    <?php else: ?>
                        <p class="mt-4 rounded-md bg-zinc-50 px-3 py-3 text-center text-sm text-zinc-400">
                            <?= $temp['is_future'] ? 'Submissions open on ' . date('d M Y', strtotime($temp['date_from'])) . '.' : 'No log was submitted for this review.' ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($m['guide_feedback'])): ?>
                        <div class="mt-4 border-l-2 border-teal-600 pl-3 text-sm">
                            <p class="text-zinc-500">Guide remarks</p>
                            <p class="whitespace-pre-line"><?= h($m['guide_feedback']) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Sidebar -->
        <aside class="space-y-6">

            <!-- Action items -->
            <section class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Action items from your guide</h3>
                    <span class="tag tag-info"><?= $pendingCount ?> pending</span>
                </div>

                <?php if (!$directives): ?>
                    <p class="muted">Nothing assigned yet.</p>
                <?php else: ?>
                    <ul class="divide-y divide-zinc-100">
                        <?php foreach ($directives as $d): ?>
                            <li class="py-2.5 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <p><?= h($d['text']) ?></p>
                                    <span class="tag shrink-0 <?= status_tag($d['status']) ?>"><?= h(ucfirst($d['status'])) ?></span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-zinc-400">
                                    <span>Week <?= $d['week_number'] ?></span>
                                    <?php if ($d['status'] === 'open'): ?>
                                        <form action="manage_instruction.php" method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="instruction_id" value="<?= (int)$d['id'] ?>">
                                            <input type="hidden" name="status" value="acknowledged">
                                            <button type="submit" class="text-teal-700 hover:underline">Acknowledge</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- Upload -->
            <section class="panel p-5">
                <h3 class="font-semibold">Add to project gallery</h3>
                <p class="muted mb-3">Screenshots, prototype photos or repository links for your guide to review.</p>

                <form action="upload_project_asset.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                    <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

                    <div>
                        <label class="label">Type</label>
                        <select name="asset_type" onchange="toggleAssetType(this.value)" class="field">
                            <option value="screenshot">UI or architecture screenshot</option>
                            <option value="photo">Hardware prototype photo</option>
                            <option value="codebase">Code repository</option>
                            <option value="demo_link">Demo video link</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Title</label>
                        <input type="text" name="title" required placeholder="e.g. YOLOv8 inference output" class="field">
                    </div>
                    <div id="assetFile">
                        <label class="label">Image (JPEG, PNG or WebP)</label>
                        <input type="file" name="asset_file" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-zinc-500 file:mr-2 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200">
                    </div>
                    <div id="assetUrl" class="hidden">
                        <label class="label">Link</label>
                        <input type="url" name="external_url" placeholder="https://github.com/..." class="field">
                    </div>
                    <div>
                        <label class="label">Caption (optional)</label>
                        <input type="text" name="caption" class="field">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Upload</button>
                </form>
            </section>

            <!-- Gallery -->
            <section class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Gallery</h3>
                    <span class="muted"><?= count($assets) ?> items</span>
                </div>

                <?php if (!$assets): ?>
                    <p class="muted">Nothing uploaded yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach ($assets as $a): ?>
                            <figure class="overflow-hidden rounded-md border border-zinc-200 text-sm">
                                <?php if (!empty($a['file_path'])): ?>
                                    <button type="button" class="block h-24 w-full bg-zinc-200" data-lightbox
                                            data-src="<?= h($a['file_path']) ?>" data-title="<?= h($a['title']) ?>" data-caption="<?= h($a['caption'] ?? '') ?>">
                                        <img src="<?= h($a['file_path']) ?>" alt="<?= h($a['title']) ?>" class="h-full w-full object-cover">
                                    </button>
                                <?php else: ?>
                                    <div class="flex h-24 flex-col items-center justify-center gap-1 bg-zinc-800 px-2 text-center text-xs text-white">
                                        <span class="text-zinc-300"><?= h(asset_type_label($a['asset_type'])) ?></span>
                                        <?php if ($url = safe_url($a['external_url'] ?? '')): ?>
                                            <a href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer" class="underline">Open link</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <figcaption class="truncate px-2 py-1.5 text-xs font-medium"><?= h($a['title']) ?></figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/media_modal.php'; ?>

<script>
function toggleAssetType(type) {
    const isLink = type === 'codebase' || type === 'demo_link';
    document.getElementById('assetFile').classList.toggle('hidden', isLink);
    document.getElementById('assetUrl').classList.toggle('hidden', !isLink);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
