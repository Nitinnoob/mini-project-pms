<?php
// views/teacher.php - Teacher / Faculty Guide Review Hub, Media Gallery & Grading Tab
require_once __DIR__ . '/../dbs.php';
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../meeting_engine.php';

require_teacher();
$user = current_user();

$activeClassroomId = $_SESSION['active_classroom_id'] ?? null;

// If no active classroom, fetch the first one created or mentored by this teacher
if (!$activeClassroomId) {
    $cStmt = $pdo->prepare("
        SELECT id FROM classrooms 
        WHERE teacher_id = ? OR id IN (SELECT classroom_id FROM projects WHERE mentor_id = ?) 
        ORDER BY id DESC LIMIT 1
    ");
    $cStmt->execute([$user['id'], $user['id']]);
    $cRow = $cStmt->fetch();
    if ($cRow) {
        $_SESSION['active_classroom_id'] = $cRow['id'];
        $activeClassroomId = $cRow['id'];
    }
}

if (!$activeClassroomId) {
    // Teacher has no classrooms yet
    header('Location: hub.php');
    exit;
}

// Fetch active classroom details
$cStmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$cStmt->execute([$activeClassroomId]);
$classroom = $cStmt->fetch();
$isCoordinator = ($classroom['teacher_id'] == $user['id']);

// Fetch all projects in this classroom
$pStmt = $pdo->prepare("
    SELECT p.*, leader.name as leader_name, leader.identifier as leader_usn,
           mentor.name as mentor_name, mentor.identifier as mentor_code,
           pm.report_marks, pm.is_finalized, pm.finalized_at
    FROM projects p
    JOIN users leader ON p.created_by = leader.id
    JOIN users mentor ON p.mentor_id = mentor.id
    LEFT JOIN project_marks pm ON p.id = pm.project_id
    WHERE p.classroom_id = ?
    ORDER BY p.id ASC
");
$pStmt->execute([$activeClassroomId]);
$projects = $pStmt->fetchAll();

// Index projects, members, assets, marks and attendance
$projectData = [];
foreach ($projects as $proj) {
    $pId = $proj['id'];

    // Members
    $mStmt = $pdo->prepare("
        SELECT u.id, u.name, u.identifier, pm.is_leader,
               sm.presentation_marks, sm.qa_marks
        FROM project_members pm
        JOIN users u ON pm.student_id = u.id
        LEFT JOIN student_marks sm ON (sm.project_id = pm.project_id AND sm.student_id = u.id)
        WHERE pm.project_id = ?
        ORDER BY pm.is_leader DESC, u.identifier ASC
    ");
    $mStmt->execute([$pId]);
    $members = $mStmt->fetchAll();

    // Assets
    $aStmt = $pdo->prepare("
        SELECT pa.*, u.name as uploader_name 
        FROM project_assets pa
        JOIN users u ON pa.uploaded_by = u.id
        WHERE pa.project_id = ?
        ORDER BY pa.is_spotlight DESC, pa.id DESC
    ");
    $aStmt->execute([$pId]);
    $assets = $aStmt->fetchAll();

    // Attendance stats
    $attendanceStats = calculate_project_attendance($pdo, $pId);

    // Meetings
    $mtStmt = $pdo->prepare("
        SELECT wm.*, submitter.name as submitter_name
        FROM weekly_meetings wm
        LEFT JOIN users submitter ON wm.submitted_by = submitter.id
        WHERE wm.project_id = ?
        ORDER BY wm.week_number ASC
    ");
    $mtStmt->execute([$pId]);
    $meetings = $mtStmt->fetchAll();

    // Meetings with attendance details and directives
    $meetingDetails = [];
    foreach ($meetings as $m) {
        $mId = $m['id'];
        // Attendance records for this meeting
        $attRecStmt = $pdo->prepare("SELECT student_id, status FROM meeting_attendance WHERE meeting_id = ?");
        $attRecStmt->execute([$mId]);
        $attMap = $attRecStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Guide Instructions / Directives
        $dirStmt = $pdo->prepare("SELECT * FROM guide_instructions WHERE meeting_id = ? ORDER BY id ASC");
        $dirStmt->execute([$mId]);
        $directives = $dirStmt->fetchAll();

        $meetingDetails[] = array_merge($m, [
            'attendance_map' => $attMap,
            'directives'     => $directives,
            'temporal'       => get_week_temporal_status($m['meeting_date'], $m['status']),
        ]);
    }

    $projectData[$pId] = [
        'project'         => $proj,
        'members'         => $members,
        'assets'          => $assets,
        'attendance'      => $attendanceStats,
        'meetings'        => $meetingDetails,
    ];
}

// Flat list of all showcase assets in this classroom
$allAssets = [];
foreach ($projectData as $pId => $d) {
    foreach ($d['assets'] as $a) {
        $allAssets[] = array_merge($a, ['project_title' => $d['project']['title']]);
    }
}

$pageTitle = 'Teacher Workspace - ' . $classroom['name'];
include __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Classroom Overview Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center space-x-2 text-xs text-indigo-700 font-semibold mb-1">
                    <span><?= htmlspecialchars($classroom['institution']) ?></span>
                    <span>&bull;</span>
                    <span><?= htmlspecialchars($classroom['department']) ?></span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-1">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        <?= htmlspecialchars($classroom['name']) ?>
                    </h1>
                    <div>
                        <?php if ($isCoordinator): ?>
                            <span class="inline-flex items-center text-xs bg-indigo-100 text-indigo-800 font-bold px-3 py-1 rounded-full border border-indigo-200">
                                👑 Section Coordinator (Full Overview)
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center text-xs bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-full border border-emerald-200">
                                🎓 Assigned Faculty Guide
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Semester Period: <span class="font-medium text-slate-700"><?= date('d M Y', strtotime($classroom['start_date'])) ?> to <?= date('d M Y', strtotime($classroom['end_date'])) ?></span>
                </p>
            </div>

            <!-- Stats & Quick Actions -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-center">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Invite Code</span>
                    <span class="font-mono font-bold text-sm text-indigo-700"><?= htmlspecialchars($classroom['invite_code']) ?></span>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-center">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Total Projects</span>
                    <span class="font-bold text-sm text-slate-800"><?= count($projects) ?></span>
                </div>

                <a href="export_marks.php?classroom_id=<?= $classroom['id'] ?>" class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Marks Ledger
                </a>

                <a href="export_marks.php?classroom_id=<?= $classroom['id'] ?>&format=csv" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 mb-6">
        <nav class="flex space-x-6">
            <button onclick="switchTab('reviews')" id="tabBtnReviews" class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-indigo-600 text-indigo-600 transition-colors">
                Saturday Reviews & Attendance
            </button>
            <button onclick="switchTab('grading')" id="tabBtnGrading" class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors">
                CIE Marks & continuous Evaluation
            </button>
            <button onclick="switchTab('showcase')" id="tabBtnShowcase" class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors flex items-center">
                <span>Media Showcase CMS</span>
                <span class="ml-2 px-1.5 py-0.2 bg-indigo-100 text-indigo-700 rounded-full text-[10px]"><?= count($allAssets) ?></span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: Saturday Reviews & Attendance -->
    <div id="tabContentReviews" class="space-y-8">
        <?php if (empty($projects)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                No project teams have registered in this classroom yet. Share the invite code <strong><?= htmlspecialchars($classroom['invite_code']) ?></strong> with students.
            </div>
        <?php else: ?>
            <?php foreach ($projectData as $pId => $data): ?>
                <?php 
                $proj = $data['project']; 
                $isMyMentee = ($proj['mentor_id'] == $user['id']);
                $canManage = ($isCoordinator || $isMyMentee);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                    <!-- Project Card Header -->
                    <div class="p-6 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded">Team #<?= $proj['id'] ?></span>
                                <span class="text-xs text-slate-500">Guide: <strong class="text-slate-700"><?= htmlspecialchars($proj['mentor_name']) ?></strong></span>
                                <?php if ($isMyMentee): ?>
                                    <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded border border-emerald-200">
                                        ★ Your Mentored Team
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($proj['title']) ?></h2>
                            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($proj['description']) ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Button to enter marks directly -->
                            <button onclick="triggerMarksModal(<?= $pId ?>)" class="px-3.5 py-2 <?= $canManage ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-slate-200 hover:bg-slate-300 text-slate-700' ?> text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <?= $canManage ? 'Grade CIE Marks' : 'View Marks' ?>
                            </button>
                        </div>
                    </div>

                    <!-- Team Members & Attendance Quick Chips -->
                    <div class="px-6 py-3 bg-white border-b border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-slate-400 uppercase font-semibold text-[10px]">Team Roster & Attendance:</span>
                        <?php foreach ($data['members'] as $m): ?>
                            <?php $att = $data['attendance'][$m['id']] ?? null; ?>
                            <div class="inline-flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1">
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($m['name']) ?></span>
                                <span class="text-slate-400 font-mono ml-1 text-[11px]">(<?= htmlspecialchars($m['identifier']) ?>)</span>
                                <?php if ($att): ?>
                                    <span class="ml-2 font-mono text-[11px] font-bold <?= $att['has_shortage'] ? 'text-red-600 bg-red-50 border border-red-200 rounded px-1' : 'text-emerald-700' ?>">
                                        <?= $att['percentage'] ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Saturday Review Meetings Grid -->
                    <div class="p-6 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Weekly Saturday Reviews Timeline</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($data['meetings'] as $m): ?>
                                <?php 
                                $temp = $m['temporal'];
                                $update = !empty($m['team_update']) ? json_decode($m['team_update'], true) : null;
                                ?>
                                <div class="bg-slate-50 border <?= $temp['is_current'] ? 'border-indigo-400 ring-2 ring-indigo-200' : 'border-slate-200' ?> rounded-xl p-4 flex flex-col justify-between">
                                    <div>
                                        <!-- Week Header & Temporal Badges -->
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-slate-800 font-mono">Week <?= $m['week_number'] ?> &bull; <?= date('d M', strtotime($m['meeting_date'])) ?></span>
                                            
                                            <?php if ($temp['is_future']): ?>
                                                <span class="text-[10px] font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full flex items-center">
                                                    🔒 Locked (<?= date('d M', strtotime($temp['date_from'])) ?>)
                                                </span>
                                            <?php elseif ($temp['is_current']): ?>
                                                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full animate-pulse">
                                                    Current Active Week
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[10px] font-bold <?= $m['status'] === 'held' ? 'text-emerald-700 bg-emerald-100' : 'text-slate-600 bg-slate-200' ?> px-2 py-0.5 rounded-full">
                                                    <?= ucfirst($m['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Student Log Content -->
                                        <?php if ($update): ?>
                                            <div class="mt-2 space-y-2 text-xs">
                                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Work Done:</span>
                                                    <p class="text-slate-800 mt-0.5 line-clamp-3"><?= htmlspecialchars($update['work_done'] ?? '') ?></p>
                                                </div>
                                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Next Steps:</span>
                                                    <p class="text-slate-800 mt-0.5 line-clamp-2"><?= htmlspecialchars($update['next_steps'] ?? '') ?></p>
                                                </div>
                                                <?php if (!empty($update['blockers']) && $update['blockers'] !== 'None reported'): ?>
                                                    <div class="bg-red-50 p-2 rounded-lg border border-red-200 text-red-700">
                                                        <span class="block text-[10px] font-bold uppercase text-red-500">Blockers:</span>
                                                        <p class="mt-0.5"><?= htmlspecialchars($update['blockers']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-400 italic mt-2">
                                                <?= $temp['is_future'] ? 'Log submission locked until review week.' : 'No progress log submitted by team.' ?>
                                            </p>
                                        <?php endif; ?>

                                        <!-- Guide Feedback -->
                                        <?php if (!empty($m['guide_feedback'])): ?>
                                            <div class="mt-3 p-2.5 bg-indigo-50/70 border border-indigo-200 rounded-lg text-xs">
                                                <span class="block text-[10px] font-bold uppercase text-indigo-700">Guide Remarks:</span>
                                                <p class="text-indigo-950 mt-0.5 italic">"<?= htmlspecialchars($m['guide_feedback']) ?>"</p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Directives issued for this meeting -->
                                        <?php if (!empty($m['directives'])): ?>
                                            <div class="mt-3 space-y-1">
                                                <span class="block text-[10px] font-bold uppercase text-slate-400">Action Items:</span>
                                                <?php foreach ($m['directives'] as $d): ?>
                                                    <div class="p-1.5 bg-white border border-slate-200 rounded text-[11px] flex items-center justify-between">
                                                        <span class="truncate pr-2 text-slate-700"><?= htmlspecialchars($d['text']) ?></span>
                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold <?= $d['status'] === 'done' ? 'bg-emerald-100 text-emerald-800' : ($d['status'] === 'acknowledged' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') ?>">
                                                            <?= ucfirst($d['status']) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Bottom Action Bar -->
                                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                                        <?php if ($temp['is_future']): ?>
                                            <span class="text-xs text-slate-400 italic">🔒 Advance lock</span>
                                        <?php elseif ($temp['is_current'] && !$temp['is_held']): ?>
                                            <?php if ($canManage): ?>
                                                <button onclick="triggerAttendanceModal(<?= $m['id'] ?>, <?= $pId ?>)" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                                    Conduct Saturday Review &rarr;
                                                </button>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-500 font-medium italic">
                                                    Guided by <?= htmlspecialchars($proj['mentor_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Concluded / Locked Review (Past week or already marked held) -->
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-md">
                                                    🔒 Concluded & Locked
                                                </span>
                                                <?php if ($canManage): ?>
                                                    <button onclick="triggerAttendanceModal(<?= $m['id'] ?>, <?= $pId ?>)" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold underline">
                                                        Audit Revision
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Quick add directive button (only allowed for active reviews or explicit audit) -->
                                        <?php if ($canManage && !$temp['is_future']): ?>
                                            <button onclick="showDirectivePrompt(<?= $m['id'] ?>)" title="Assign Action Item" class="p-1.5 text-slate-500 hover:text-indigo-600 rounded hover:bg-slate-200 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- TAB 2: CIE Marks & Continuous Evaluation -->
    <div id="tabContentGrading" class="hidden space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Continuous Internal Evaluation (CIE) Scheme</h2>
                    <p class="text-xs text-slate-500">Official VTU / Autonomous 50 Report / 25 Presentation / 25 Viva Voce split (100 Marks Total).</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="export_marks.php?classroom_id=<?= $classroom['id'] ?>" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition-colors">
                        Print Landscape Ledger
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-3">Project Title</th>
                            <th class="p-3">USN</th>
                            <th class="p-3">Student Name</th>
                            <th class="p-3 text-center">Attendance %</th>
                            <th class="p-3 text-center">Report (50)</th>
                            <th class="p-3 text-center">Pres (25)</th>
                            <th class="p-3 text-center">Viva (25)</th>
                            <th class="p-3 text-center font-bold text-indigo-900">Total (100)</th>
                            <th class="p-3 text-center">Lock Status</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($projectData as $pId => $data): ?>
                            <?php 
                            $proj = $data['project']; 
                            $reportScore = $proj['report_marks'] !== null ? (float)$proj['report_marks'] : null;
                            ?>
                            <?php foreach ($data['members'] as $idx => $m): ?>
                                <?php 
                                $att = $data['attendance'][$m['id']] ?? null;
                                $pres = $m['presentation_marks'] !== null ? (float)$m['presentation_marks'] : null;
                                $qa = $m['qa_marks'] !== null ? (float)$m['qa_marks'] : null;
                                $total = ($reportScore !== null || $pres !== null || $qa !== null) ? (($reportScore ?? 0) + ($pres ?? 0) + ($qa ?? 0)) : null;
                                ?>
                                <tr class="hover:bg-slate-50">
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= count($data['members']) ?>" class="p-3 align-top font-bold text-slate-900 border-r border-slate-100 max-w-xs">
                                            <?= htmlspecialchars($proj['title']) ?>
                                            <span class="block text-[10px] text-slate-400 font-normal mt-0.5">Guide: <?= htmlspecialchars($proj['mentor_name']) ?></span>
                                        </td>
                                    <?php endif; ?>
                                    <td class="p-3 font-mono font-bold text-slate-800"><?= htmlspecialchars($m['identifier']) ?></td>
                                    <td class="p-3 font-medium">
                                        <?= htmlspecialchars($m['name']) ?>
                                        <?= $m['is_leader'] ? '<span class="text-[9px] text-indigo-700 bg-indigo-50 border border-indigo-200 px-1 rounded ml-1 font-semibold">Leader</span>' : '' ?>
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        <span class="<?= ($att && $att['has_shortage']) ? 'text-red-600 font-bold bg-red-50 border border-red-200 px-1 py-0.5 rounded' : 'text-slate-800' ?>">
                                            <?= $att ? $att['percentage'] . '%' : '100%' ?>
                                        </span>
                                    </td>
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= count($data['members']) ?>" class="p-3 text-center font-mono align-top border-r border-slate-100">
                                            <?= $reportScore !== null ? number_format($reportScore, 1) : '<span class="text-slate-300">-</span>' ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="p-3 text-center font-mono">
                                        <?= $pres !== null ? number_format($pres, 1) : '<span class="text-slate-300">-</span>' ?>
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        <?= $qa !== null ? number_format($qa, 1) : '<span class="text-slate-300">-</span>' ?>
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold text-indigo-900 text-sm">
                                        <?= $total !== null ? number_format($total, 1) : '<span class="text-slate-300">-</span>' ?>
                                    </td>
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= count($data['members']) ?>" class="p-3 text-center align-top border-l border-slate-100">
                                            <?php if ($proj['is_finalized']): ?>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Finalized 🔒</span>
                                            <?php else: ?>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td rowspan="<?= count($data['members']) ?>" class="p-3 text-center align-top">
                                            <button onclick="triggerMarksModal(<?= $pId ?>)" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors whitespace-nowrap">
                                                <?= $proj['is_finalized'] ? 'Edit Marks' : 'Enter Marks' ?>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Media Showcase CMS -->
    <div id="tabContentShowcase" class="hidden space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Project Deliverables & Media Showcase</h2>
                    <p class="text-xs text-slate-500">Visual evidence library for departmental exhibition reels, vivas, and NAAC inspections.</p>
                </div>
            </div>

            <?php if (empty($allAssets)): ?>
                <div class="p-12 text-center text-slate-400 italic">
                    No student project screenshots, hardware prototype photos or codebase links have been uploaded yet.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($allAssets as $asset): ?>
                        <div class="bg-white rounded-xl border <?= $asset['is_spotlight'] ? 'border-amber-400 ring-2 ring-amber-300' : 'border-slate-200' ?> overflow-hidden shadow-sm flex flex-col justify-between">
                            <div>
                                <!-- Image or URL Preview -->
                                <?php if (!empty($asset['file_path'])): ?>
                                    <div class="h-44 bg-slate-900 relative group cursor-pointer" onclick="openMediaLightbox('<?= htmlspecialchars($asset['file_path']) ?>', '<?= htmlspecialchars(addslashes($asset['title'])) ?>', '<?= htmlspecialchars($asset['asset_type']) ?>', '<?= htmlspecialchars(addslashes($asset['caption'] ?? '')) ?>')">
                                        <img src="<?= htmlspecialchars($asset['file_path']) ?>" alt="<?= htmlspecialchars($asset['title']) ?>" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'250\' viewBox=\'0 0 400 250\'><rect fill=\'%230f172a\' width=\'400\' height=\'250\'/><text fill=\'%2394a3b8\' x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'14\'>Visual Project Evidence</text></svg>';" class="w-full h-full object-cover group-hover:opacity-90 transition-opacity">
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold transition-opacity">
                                            🔍 Inspect Lightbox
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="h-44 bg-gradient-to-br from-indigo-800 to-slate-900 p-6 flex flex-col items-center justify-center text-white text-center">
                                        <svg class="w-10 h-10 mb-2 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-200"><?= $asset['asset_type'] === 'codebase' ? 'Source Repository' : 'Video Demo' ?></span>
                                        <a href="<?= htmlspecialchars($asset['external_url']) ?>" target="_blank" class="mt-3 px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-xs font-semibold underline text-white">
                                            Open External Link &rarr;
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-mono uppercase font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                            <?= htmlspecialchars($asset['asset_type']) ?>
                                        </span>
                                        <?php if ($asset['is_spotlight']): ?>
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full flex items-center">
                                                ⭐ Spotlight
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h4 class="text-xs font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($asset['title']) ?></h4>
                                    <p class="text-[11px] text-slate-400 mt-0.5 truncate"><?= htmlspecialchars($asset['project_title']) ?></p>

                                    <?php if (!empty($asset['caption'])): ?>
                                        <p class="text-[11px] text-slate-600 mt-2 line-clamp-2 italic">"<?= htmlspecialchars($asset['caption']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Faculty Spotlight & Management Controls -->
                            <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                                <form action="manage_project_asset.php" method="POST">
                                    <input type="hidden" name="action" value="toggle_spotlight">
                                    <input type="hidden" name="asset_id" value="<?= $asset['id'] ?>">
                                    <button type="submit" class="text-xs font-semibold <?= $asset['is_spotlight'] ? 'text-amber-600 hover:text-amber-700' : 'text-slate-500 hover:text-amber-600' ?>">
                                        <?= $asset['is_spotlight'] ? '⭐ Unpin' : '☆ Pin to Exhibition' ?>
                                    </button>
                                </form>

                                <form action="manage_project_asset.php" method="POST" onsubmit="return confirm('Delete this asset?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="asset_id" value="<?= $asset['id'] ?>">
                                    <button type="submit" class="text-[11px] text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Includes -->
<?php include __DIR__ . '/media_modal.php'; ?>
<?php include __DIR__ . '/attendance_modal.php'; ?>
<?php include __DIR__ . '/marks_modal.php'; ?>

<!-- Client Data Store for Modals -->
<script>
const projectDataStore = <?= json_encode($projectData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function switchTab(tab) {
    document.getElementById('tabContentReviews').classList.add('hidden');
    document.getElementById('tabContentGrading').classList.add('hidden');
    document.getElementById('tabContentShowcase').classList.add('hidden');

    document.getElementById('tabBtnReviews').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors';
    document.getElementById('tabBtnGrading').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors';
    document.getElementById('tabBtnShowcase').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors flex items-center';

    if (tab === 'reviews') {
        document.getElementById('tabContentReviews').classList.remove('hidden');
        document.getElementById('tabBtnReviews').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-indigo-600 text-indigo-600 transition-colors';
    } else if (tab === 'grading') {
        document.getElementById('tabContentGrading').classList.remove('hidden');
        document.getElementById('tabBtnGrading').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-indigo-600 text-indigo-600 transition-colors';
    } else if (tab === 'showcase') {
        document.getElementById('tabContentShowcase').classList.remove('hidden');
        document.getElementById('tabBtnShowcase').className = 'pb-3 text-xs font-bold uppercase tracking-wider border-b-2 border-indigo-600 text-indigo-600 transition-colors flex items-center';
    }
}

function triggerMarksModal(projectId) {
    const data = projectDataStore[projectId];
    if (!data) return;

    openMarksModal({
        project_id: projectId,
        title: data.project.title,
        description: data.project.description,
        report_marks: data.project.report_marks,
        is_finalized: data.project.is_finalized,
        members: data.members,
        assets: data.assets,
    });
}

function triggerAttendanceModal(meetingId, projectId) {
    const pData = projectDataStore[projectId];
    if (!pData) return;

    const meeting = pData.meetings.find(m => m.id == meetingId);
    if (!meeting) return;

    const membersWithAtt = pData.members.map(m => {
        return {
            id: m.id,
            name: m.name,
            identifier: m.identifier,
            is_leader: m.is_leader,
            current_status: meeting.attendance_map[m.id] || 'present',
        };
    });

    openAttendanceModal({
        meeting_id: meeting.id,
        project_id: projectId,
        week_number: meeting.week_number,
        meeting_date: meeting.meeting_date,
        project_title: pData.project.title,
        status: meeting.status,
        is_locked: meeting.temporal.is_locked,
        guide_feedback: meeting.guide_feedback,
        members: membersWithAtt,
    });
}

function showDirectivePrompt(meetingId) {
    const text = prompt('Enter Actionable Mentor Directive / Action Item for this review:');
    if (!text || text.trim() === '') return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'manage_instruction.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="meeting_id" value="${meetingId}">
        <input type="hidden" name="text" value="${text.replace(/"/g, '&quot;')}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
