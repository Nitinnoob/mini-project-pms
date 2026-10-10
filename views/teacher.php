<?php
// views/teacher.php - Teacher workspace: reviews & attendance, CIE marks, media showcase
require_once __DIR__ . '/../dbs.php';
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../meeting_engine.php';

require_teacher();
$user = current_user();

// Use the active classroom, or fall back to the latest one this teacher owns or mentors in
$classroomId = $_SESSION['active_classroom_id'] ?? null;
if (!$classroomId) {
    $stmt = $pdo->prepare("
        SELECT id FROM classrooms
        WHERE teacher_id = ? OR id IN (SELECT classroom_id FROM projects WHERE mentor_id = ?)
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $classroomId = $_SESSION['active_classroom_id'] = $stmt->fetchColumn() ?: null;
}
if (!$classroomId) {
    header('Location: hub.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmt->execute([$classroomId]);
$classroom = $stmt->fetch();
$isCoordinator = ($classroom['teacher_id'] == $user['id']);

$stmt = $pdo->prepare("
    SELECT p.*, mentor.name AS mentor_name, pmk.report_marks, pmk.is_finalized
    FROM projects p
    JOIN users mentor ON p.mentor_id = mentor.id
    LEFT JOIN project_marks pmk ON p.id = pmk.project_id
    WHERE p.classroom_id = ?
    ORDER BY p.id ASC
");
$stmt->execute([$classroomId]);
$projects = $stmt->fetchAll();

$membersStmt = $pdo->prepare("
    SELECT u.id, u.name, u.identifier, pm.is_leader, sm.presentation_marks, sm.qa_marks
    FROM project_members pm
    JOIN users u ON pm.student_id = u.id
    LEFT JOIN student_marks sm ON (sm.project_id = pm.project_id AND sm.student_id = u.id)
    WHERE pm.project_id = ?
    ORDER BY pm.is_leader DESC, u.identifier ASC
");
$assetsStmt = $pdo->prepare("
    SELECT pa.*, u.name AS uploader_name
    FROM project_assets pa
    JOIN users u ON pa.uploaded_by = u.id
    WHERE pa.project_id = ?
    ORDER BY pa.is_spotlight DESC, pa.id DESC
");
$meetingsStmt   = $pdo->prepare("SELECT * FROM weekly_meetings WHERE project_id = ? ORDER BY week_number ASC");
$attendanceStmt = $pdo->prepare("SELECT student_id, status FROM meeting_attendance WHERE meeting_id = ?");
$directivesStmt = $pdo->prepare("SELECT * FROM guide_instructions WHERE meeting_id = ? ORDER BY id ASC");

// $projectData feeds the page; $modalData is the trimmed copy sent to the modals' JavaScript
$projectData = [];
$modalData   = [];
foreach ($projects as $proj) {
    $pId = $proj['id'];

    $membersStmt->execute([$pId]);
    $members = $membersStmt->fetchAll();

    $assetsStmt->execute([$pId]);
    $assets = $assetsStmt->fetchAll();

    $meetingsStmt->execute([$pId]);
    $meetings = [];
    $modalMeetings = [];
    foreach ($meetingsStmt->fetchAll() as $m) {
        $attendanceStmt->execute([$m['id']]);
        $directivesStmt->execute([$m['id']]);
        $m['attendance'] = $attendanceStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $m['directives'] = $directivesStmt->fetchAll();
        $m['temporal']   = get_week_temporal_status($m['meeting_date'], $m['status']);
        $meetings[] = $m;

        $modalMeetings[$m['id']] = [
            'week_number'    => (int)$m['week_number'],
            'meeting_date'   => $m['meeting_date'],
            'status'         => $m['status'],
            'guide_feedback' => $m['guide_feedback'],
            'is_locked'      => (bool)$m['temporal']['is_locked'],
            'attendance'     => $m['attendance'],
        ];
    }

    $projectData[$pId] = [
        'project'    => $proj,
        'members'    => $members,
        'assets'     => $assets,
        'attendance' => calculate_project_attendance($pdo, $pId),
        'meetings'   => $meetings,
    ];

    $modalData[$pId] = [
        'id'           => (int)$pId,
        'title'        => $proj['title'],
        'description'  => $proj['description'],
        'report_marks' => $proj['report_marks'],
        'is_finalized' => (int)$proj['is_finalized'],
        'members'      => array_map(fn($m) => [
            'id'                 => (int)$m['id'],
            'name'               => $m['name'],
            'identifier'         => $m['identifier'],
            'is_leader'          => (bool)$m['is_leader'],
            'presentation_marks' => $m['presentation_marks'],
            'qa_marks'           => $m['qa_marks'],
        ], $members),
        'assets'       => array_map(fn($a) => [
            'title'        => $a['title'],
            'caption'      => $a['caption'] ?? '',
            'file_path'    => $a['file_path'],
            'external_url' => $a['external_url'],
        ], $assets),
        'meetings'     => $modalMeetings,
    ];
}

// Showcase counts for the filter chips
$assetTotal = 0;
$spotlightTotal = 0;
$typeCounts = [];
foreach ($projectData as $d) {
    foreach ($d['assets'] as $a) {
        $assetTotal++;
        $typeCounts[$a['asset_type']] = ($typeCounts[$a['asset_type']] ?? 0) + 1;
        if (!empty($a['is_spotlight'])) $spotlightTotal++;
    }
}

$pageTitle = 'Teacher Workspace - ' . $classroom['name'];
include __DIR__ . '/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Classroom overview -->
    <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <p class="text-sm text-zinc-500"><?= h($classroom['institution']) ?>, <?= h($classroom['department']) ?></p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight"><?= h($classroom['name']) ?></h1>
            <p class="mt-1 text-sm text-zinc-500">
                <?= $isCoordinator ? 'Section coordinator' : 'Faculty guide' ?> &middot;
                <?= date('d M Y', strtotime($classroom['start_date'])) ?> to <?= date('d M Y', strtotime($classroom['end_date'])) ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <p class="text-sm">Invite code <strong class="font-semibold text-teal-800"><?= h($classroom['invite_code']) ?></strong></p>
            <p class="text-sm"><strong class="font-semibold"><?= count($projects) ?></strong> projects</p>
            <a href="export_marks.php?classroom_id=<?= (int)$classroom['id'] ?>" class="btn">Print marks ledger</a>
            <a href="export_marks.php?classroom_id=<?= (int)$classroom['id'] ?>&format=csv" class="btn">Export CSV</a>
        </div>
    </section>

    <!-- Tabs -->
    <nav class="flex border-b border-zinc-200" role="tablist">
        <button type="button" class="tab active" data-tab="reviews" onclick="switchTab('reviews')">Reviews and attendance</button>
        <button type="button" class="tab" data-tab="marks" onclick="switchTab('marks')">CIE marks</button>
        <button type="button" class="tab" data-tab="showcase" onclick="switchTab('showcase')">Showcase (<?= $assetTotal ?>)</button>
    </nav>

    <!-- Tab: reviews and attendance -->
    <div data-pane="reviews" class="space-y-6">
        <?php if (!$projects): ?>
            <div class="panel p-8 text-center text-zinc-500">
                No teams have registered yet. Share the invite code <strong><?= h($classroom['invite_code']) ?></strong> with students.
            </div>
        <?php endif; ?>

        <?php foreach ($projectData as $pId => $data):
            $proj      = $data['project'];
            $isMentor  = ($proj['mentor_id'] == $user['id']);
            $canManage = ($isCoordinator || $isMentor);
        ?>
            <section class="panel overflow-hidden">
                <div class="p-5 border-b border-zinc-200">
                    <p class="text-sm text-zinc-500">
                        Team #<?= (int)$proj['id'] ?> &middot; Guide: <?= h($proj['mentor_name']) ?>
                        <?php if ($isMentor): ?><span class="tag tag-ok ml-1">Your team</span><?php endif; ?>
                    </p>
                    <h2 class="text-lg font-bold"><?= h($proj['title']) ?></h2>
                    <p class="muted"><?= h($proj['description']) ?></p>

                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <?php foreach ($data['members'] as $m): $att = $data['attendance'][$m['id']] ?? null; ?>
                            <span class="rounded-md border border-zinc-200 px-2.5 py-1">
                                <?= h($m['name']) ?> <span class="text-zinc-500">(<?= h($m['identifier']) ?>)</span>
                                <?php if ($att): ?>
                                    <span class="ml-1 font-medium tabular-nums <?= $att['has_shortage'] ? 'text-red-600' : 'text-emerald-700' ?>"><?= $att['percentage'] ?>%</span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                    <?php foreach ($data['meetings'] as $m):
                        $temp   = $m['temporal'];
                        $update = !empty($m['team_update']) ? json_decode($m['team_update'], true) : null;
                    ?>
                        <article class="flex flex-col justify-between rounded-md border p-4 <?= $temp['is_current'] ? 'border-teal-600 ring-1 ring-teal-600' : 'border-zinc-200' ?>">
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold">Week <?= $m['week_number'] ?> <span class="font-normal text-zinc-500"><?= date('d M', strtotime($m['meeting_date'])) ?></span></h3>
                                    <?php if ($temp['is_future']): ?>
                                        <span class="tag tag-mute">Opens <?= date('d M', strtotime($temp['date_from'])) ?></span>
                                    <?php elseif ($temp['is_current']): ?>
                                        <span class="tag tag-info">Current week</span>
                                    <?php else: ?>
                                        <span class="tag <?= $m['status'] === 'held' ? 'tag-ok' : 'tag-mute' ?>"><?= h(ucfirst($m['status'])) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($update): ?>
                                    <div>
                                        <p class="text-zinc-500">Work done</p>
                                        <p class="line-clamp-3"><?= h($update['work_done'] ?? '') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-zinc-500">Next steps</p>
                                        <p class="line-clamp-2"><?= h($update['next_steps'] ?? '') ?></p>
                                    </div>
                                    <?php if (!empty($update['blockers']) && $update['blockers'] !== 'None reported'): ?>
                                        <p class="rounded bg-red-50 px-2 py-1 text-red-800"><span class="text-red-600">Blockers:</span> <?= h($update['blockers']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-zinc-400"><?= $temp['is_future'] ? 'Log opens in the review week.' : 'No log submitted.' ?></p>
                                <?php endif; ?>

                                <?php if (!empty($m['guide_feedback'])): ?>
                                    <div class="border-l-2 border-teal-600 pl-3">
                                        <p class="text-zinc-500">Guide remarks</p>
                                        <p><?= h($m['guide_feedback']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($m['directives']): ?>
                                    <ul class="space-y-1">
                                        <?php foreach ($m['directives'] as $d): ?>
                                            <li class="flex items-center justify-between gap-2 text-xs">
                                                <span class="truncate"><?= h($d['text']) ?></span>
                                                <span class="tag shrink-0 <?= status_tag($d['status']) ?>"><?= h(ucfirst($d['status'])) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-zinc-100 pt-3 text-sm">
                                <?php if ($temp['is_future']): ?>
                                    <span class="text-zinc-400">Not open yet</span>
                                <?php elseif ($temp['is_current'] && !$temp['is_held']): ?>
                                    <?php if ($canManage): ?>
                                        <button type="button" class="btn btn-primary" onclick="openAttendanceModal(projectData[<?= (int)$pId ?>], <?= (int)$m['id'] ?>)">Conduct review</button>
                                    <?php else: ?>
                                        <span class="text-zinc-500">Guided by <?= h($proj['mentor_name']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="tag tag-mute">Concluded</span>
                                    <?php if ($canManage): ?>
                                        <button type="button" class="text-teal-700 hover:underline" onclick="openAttendanceModal(projectData[<?= (int)$pId ?>], <?= (int)$m['id'] ?>)">Revise</button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($canManage && !$temp['is_future']): ?>
                                    <button type="button" class="text-teal-700 hover:underline" onclick="addDirective(<?= (int)$m['id'] ?>)">+ Action item</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- Tab: CIE marks -->
    <div data-pane="marks" class="hidden">
        <div class="panel overflow-x-auto">
            <div class="p-5">
                <h2 class="text-lg font-bold">Continuous internal evaluation</h2>
                <p class="muted">Report 50, presentation 25, viva 25. 100 marks in total.</p>
            </div>

            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-600">
                    <tr>
                        <th class="p-3 font-medium">Project</th>
                        <th class="p-3 font-medium">USN</th>
                        <th class="p-3 font-medium">Student</th>
                        <th class="p-3 font-medium text-center">Attendance</th>
                        <th class="p-3 font-medium text-center">Report (50)</th>
                        <th class="p-3 font-medium text-center">Presentation (25)</th>
                        <th class="p-3 font-medium text-center">Viva (25)</th>
                        <th class="p-3 font-medium text-center">Total (100)</th>
                        <th class="p-3 font-medium text-center">Status</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($projectData as $pId => $data):
                        $proj      = $data['project'];
                        $rows      = count($data['members']);
                        $canManage = ($isCoordinator || $proj['mentor_id'] == $user['id']);
                        foreach ($data['members'] as $i => $m):
                            $att = $data['attendance'][$m['id']] ?? null;
                    ?>
                        <tr>
                            <?php if ($i === 0): ?>
                                <td rowspan="<?= $rows ?>" class="p-3 align-top font-medium max-w-xs">
                                    <?= h($proj['title']) ?>
                                    <span class="block text-xs font-normal text-zinc-500">Guide: <?= h($proj['mentor_name']) ?></span>
                                </td>
                            <?php endif; ?>
                            <td class="p-3"><?= h($m['identifier']) ?></td>
                            <td class="p-3"><?= h($m['name']) ?><?= $m['is_leader'] ? ' <span class="tag tag-info">Leader</span>' : '' ?></td>
                            <td class="p-3 text-center tabular-nums <?= ($att && $att['has_shortage']) ? 'font-semibold text-red-600' : '' ?>"><?= $att ? $att['percentage'] : 100 ?>%</td>
                            <?php if ($i === 0): ?>
                                <td rowspan="<?= $rows ?>" class="p-3 align-top text-center tabular-nums"><?= fmt_mark($proj['report_marks']) ?></td>
                            <?php endif; ?>
                            <td class="p-3 text-center tabular-nums"><?= fmt_mark($m['presentation_marks']) ?></td>
                            <td class="p-3 text-center tabular-nums"><?= fmt_mark($m['qa_marks']) ?></td>
                            <td class="p-3 text-center font-semibold tabular-nums"><?= fmt_mark(cie_total($proj['report_marks'], $m['presentation_marks'], $m['qa_marks'])) ?></td>
                            <?php if ($i === 0): ?>
                                <td rowspan="<?= $rows ?>" class="p-3 align-top text-center">
                                    <span class="tag <?= $proj['is_finalized'] ? 'tag-ok' : 'tag-warn' ?>"><?= $proj['is_finalized'] ? 'Finalized' : 'Draft' ?></span>
                                </td>
                                <td rowspan="<?= $rows ?>" class="p-3 align-top text-center">
                                    <button type="button" class="btn" onclick="openMarksModal(projectData[<?= (int)$pId ?>])">
                                        <?= !$canManage ? 'View' : ($proj['is_finalized'] ? 'Edit' : 'Enter') ?> marks
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: media showcase -->
    <div data-pane="showcase" class="hidden space-y-6">
        <div>
            <h2 class="text-lg font-bold">Showcase</h2>
            <p class="muted">Uploads from every team. Pin the best work to the spotlight.</p>
        </div>

        <?php if ($assetTotal === 0): ?>
            <div class="panel border-dashed p-10 text-center text-zinc-500">Nothing uploaded yet. Screenshots, photos and links added by teams will appear here.</div>
        <?php else: ?>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="chip active" data-chip="all">All (<?= $assetTotal ?>)</button>
                <?php foreach ($typeCounts as $type => $count): ?>
                    <button type="button" class="chip" data-chip="<?= h($type) ?>"><?= h(asset_type_label($type)) ?> (<?= $count ?>)</button>
                <?php endforeach; ?>
                <?php if ($spotlightTotal): ?>
                    <button type="button" class="chip" data-chip="spotlight">Spotlight (<?= $spotlightTotal ?>)</button>
                <?php endif; ?>
            </div>

            <?php foreach ($projectData as $pId => $data):
                if (!$data['assets']) continue;
                $proj      = $data['project'];
                $canManage = ($isCoordinator || $proj['mentor_id'] == $user['id']);
            ?>
                <section class="team-assets">
                    <h3 class="mb-3 font-semibold">
                        <?= h($proj['title']) ?>
                        <span class="font-normal text-zinc-500">Team #<?= (int)$proj['id'] ?>, guide <?= h($proj['mentor_name']) ?></span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($data['assets'] as $a):
                            $isSpot = !empty($a['is_spotlight']);
                            $url    = empty($a['file_path']) ? safe_url($a['external_url'] ?? '') : '';
                        ?>
                            <article class="asset-card panel overflow-hidden <?= $isSpot ? '!border-amber-400' : '' ?>" data-type="<?= h($a['asset_type']) ?>" data-spot="<?= $isSpot ? 1 : 0 ?>">
                                <?php if (!empty($a['file_path'])): ?>
                                    <button type="button" class="block aspect-[4/3] w-full bg-zinc-200" data-lightbox
                                            data-src="<?= h($a['file_path']) ?>" data-title="<?= h($a['title']) ?>" data-caption="<?= h($a['caption'] ?? '') ?>">
                                        <img src="<?= h($a['file_path']) ?>" alt="<?= h($a['title']) ?>" loading="lazy" class="h-full w-full object-cover">
                                    </button>
                                <?php else: ?>
                                    <div class="flex aspect-[4/3] flex-col items-center justify-center gap-1 bg-zinc-800 px-3 text-center text-sm text-white">
                                        <?php if ($url): ?>
                                            <a href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer" class="max-w-full truncate underline"><?= h(preg_replace('/^www\./', '', (string)parse_url($url, PHP_URL_HOST))) ?></a>
                                        <?php else: ?>
                                            <span class="italic text-zinc-400">Link unavailable</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="p-3 text-sm">
                                    <div class="mb-1 flex items-center gap-1.5">
                                        <span class="tag tag-mute"><?= h(asset_type_label($a['asset_type'])) ?></span>
                                        <?php if ($isSpot): ?><span class="tag tag-warn">★ Spotlight</span><?php endif; ?>
                                    </div>
                                    <h4 class="truncate font-medium" title="<?= h($a['title']) ?>"><?= h($a['title']) ?></h4>
                                    <p class="line-clamp-2 text-xs text-zinc-500"><?= h($a['caption'] ?? '') ?></p>
                                    <p class="mt-2 text-xs text-zinc-400">
                                        <?= h($a['uploader_name']) ?><?= !empty($a['created_at']) ? ', ' . date('d M Y', strtotime($a['created_at'])) : '' ?>
                                    </p>

                                    <?php if ($canManage): ?>
                                        <div class="mt-2 flex items-center gap-3 border-t border-zinc-100 pt-2 text-xs">
                                            <form action="manage_project_asset.php" method="POST">
                                                <input type="hidden" name="action" value="toggle_spotlight">
                                                <input type="hidden" name="asset_id" value="<?= (int)$a['id'] ?>">
                                                <button type="submit" class="text-teal-700 hover:underline"><?= $isSpot ? 'Unpin' : 'Pin to spotlight' ?></button>
                                            </form>
                                            <form action="manage_project_asset.php" method="POST" onsubmit="return confirm('Delete this asset permanently?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="asset_id" value="<?= (int)$a['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/media_modal.php'; ?>
<?php include __DIR__ . '/attendance_modal.php'; ?>
<?php include __DIR__ . '/marks_modal.php'; ?>

<script>
const projectData = <?= json_encode($modalData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

// Tabs. The choice is remembered so pin, delete and save-marks reloads return to the same tab.
function switchTab(name) {
    document.querySelectorAll('[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    document.querySelectorAll('[data-pane]').forEach(p => p.classList.toggle('hidden', p.dataset.pane !== name));
    try { sessionStorage.setItem('pmsTeacherTab', name); } catch (e) {}
}
try { switchTab(sessionStorage.getItem('pmsTeacherTab') || 'reviews'); } catch (e) {}

// Showcase filter
document.querySelectorAll('[data-chip]').forEach(chip => chip.addEventListener('click', () => {
    const filter = chip.dataset.chip;
    document.querySelectorAll('[data-chip]').forEach(c => c.classList.toggle('active', c === chip));
    document.querySelectorAll('.asset-card').forEach(card => {
        const show = filter === 'all' || (filter === 'spotlight' ? card.dataset.spot === '1' : card.dataset.type === filter);
        card.classList.toggle('hidden', !show);
    });
    document.querySelectorAll('.team-assets').forEach(s => s.classList.toggle('hidden', !s.querySelector('.asset-card:not(.hidden)')));
}));

function addDirective(meetingId) {
    const text = prompt('Action item for this review:');
    if (!text || !text.trim()) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'manage_instruction.php';
    Object.entries({ action: 'create', meeting_id: meetingId, text: text }).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
