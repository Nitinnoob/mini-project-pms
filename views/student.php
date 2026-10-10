<?php
// views/student.php - Student Workspace: Weekly Review Submission, Media Upload & Shortage Alerts
require_once __DIR__ . '/../dbs.php';
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../meeting_engine.php';

require_student();
$user = current_user();

$activeClassroomId = $_SESSION['active_classroom_id'] ?? null;

// If no active classroom, fetch the first one student is enrolled in
if (!$activeClassroomId) {
    $cStmt = $pdo->prepare("SELECT classroom_id FROM classroom_students WHERE student_id = ? ORDER BY enrolled_at DESC LIMIT 1");
    $cStmt->execute([$user['id']]);
    $cRow = $cStmt->fetch();
    if ($cRow) {
        $_SESSION['active_classroom_id'] = $cRow['classroom_id'];
        $activeClassroomId = $cRow['classroom_id'];
    }
}

if (!$activeClassroomId) {
    // Student not enrolled in any classroom yet
    header('Location: hub.php');
    exit;
}

// Fetch classroom details
$cStmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$cStmt->execute([$activeClassroomId]);
$classroom = $cStmt->fetch();

// Fetch student's project in this classroom
$pStmt = $pdo->prepare("
    SELECT p.*, leader.name as leader_name, leader.identifier as leader_usn,
           mentor.name as mentor_name, mentor.identifier as mentor_code,
           pm.is_leader,
           pmarks.report_marks, pmarks.is_finalized,
           smarks.presentation_marks, smarks.qa_marks
    FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    JOIN users leader ON p.created_by = leader.id
    JOIN users mentor ON p.mentor_id = mentor.id
    LEFT JOIN project_marks pmarks ON p.id = pmarks.project_id
    LEFT JOIN student_marks smarks ON (p.id = smarks.project_id AND smarks.student_id = ?)
    WHERE p.classroom_id = ? AND pm.student_id = ?
    LIMIT 1
");
$pStmt->execute([$user['id'], $activeClassroomId, $user['id']]);
$project = $pStmt->fetch();

// If no project formed yet, guide student to create or wait
if (!$project) {
    $pageTitle = 'Dashboard - PMS';
    include __DIR__ . '/header.php';
    ?>
    <div class="max-w-4xl mx-auto px-4 py-12 text-center">
        <div class="bg-white rounded-2xl border border-slate-200 p-10 shadow-sm">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Form Your Project Team</h2>
            <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                You are enrolled in <strong class="text-slate-800"><?= htmlspecialchars($classroom['name']) ?></strong> (<?= htmlspecialchars($classroom['institution']) ?>). Register your project title, select your faculty guide, and add up to 3 batchmates.
            </p>
            <a href="create_project.php?classroom_id=<?= $classroom['id'] ?>" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-md transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Form Project Team Now
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

$projectId = $project['id'];

// Fetch teammates
$memStmt = $pdo->prepare("
    SELECT u.id, u.name, u.identifier, pm.is_leader 
    FROM project_members pm
    JOIN users u ON pm.student_id = u.id
    WHERE pm.project_id = ?
    ORDER BY pm.is_leader DESC, u.name ASC
");
$memStmt->execute([$projectId]);
$teammates = $memStmt->fetchAll();

// Calculate Attendance Stats
$attendanceStats = calculate_project_attendance($pdo, $projectId);
$myAttendance = $attendanceStats[$user['id']] ?? ['percentage' => 100.0, 'has_shortage' => false, 'attended' => 0, 'total_held' => 0];

// Fetch Meetings
$mStmt = $pdo->prepare("
    SELECT wm.*, submitter.name as submitter_name
    FROM weekly_meetings wm
    LEFT JOIN users submitter ON wm.submitted_by = submitter.id
    WHERE wm.project_id = ?
    ORDER BY wm.week_number ASC
");
$mStmt->execute([$projectId]);
$meetings = $mStmt->fetchAll();

// Fetch Directives with Rollover status
$dirStmt = $pdo->prepare("
    SELECT gi.*, wm.week_number, wm.meeting_date
    FROM guide_instructions gi
    JOIN weekly_meetings wm ON gi.meeting_id = wm.id
    WHERE wm.project_id = ?
    ORDER BY gi.created_at DESC
");
$dirStmt->execute([$projectId]);
$allDirectives = $dirStmt->fetchAll();

// Fetch Assets
$aStmt = $pdo->prepare("SELECT * FROM project_assets WHERE project_id = ? ORDER BY is_spotlight DESC, id DESC");
$aStmt->execute([$projectId]);
$assets = $aStmt->fetchAll();

// Total CIE marks
$repMark  = $project['report_marks'] !== null ? (float)$project['report_marks'] : null;
$presMark = $project['presentation_marks'] !== null ? (float)$project['presentation_marks'] : null;
$qaMark   = $project['qa_marks'] !== null ? (float)$project['qa_marks'] : null;
$totalCie = ($repMark !== null || $presMark !== null || $qaMark !== null) ? (($repMark ?? 0) + ($presMark ?? 0) + ($qaMark ?? 0)) : null;

$pageTitle = 'Student Portal - ' . $project['title'];
include __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- 75% Attendance Shortage Alert Banner -->
    <?php if ($myAttendance['has_shortage']): ?>
        <div class="mb-8 bg-red-600 rounded-2xl p-5 sm:p-6 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-bounce">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold uppercase tracking-wide">University 75% Attendance Shortage Alert</h2>
                    <p class="text-xs text-red-100 mt-0.5">
                        Your cumulative attendance is currently <strong class="font-bold underline text-white font-mono"><?= $myAttendance['percentage'] ?>%</strong> (<?= $myAttendance['attended'] ?> of <?= $myAttendance['total_held'] ?> reviews attended). Regular weekend attendance is mandatory to sit for the final project viva.
                    </p>
                </div>
            </div>
            <span class="px-3 py-1 bg-white text-red-700 font-extrabold text-xs rounded-full uppercase tracking-wider whitespace-nowrap shadow-sm">
                Shortage Flagged
            </span>
        </div>
    <?php endif; ?>

    <!-- Project Identity & Summary Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="max-w-3xl">
                <div class="flex items-center space-x-2 text-xs text-indigo-700 font-semibold mb-2">
                    <span><?= htmlspecialchars($classroom['institution']) ?></span>
                    <span>&bull;</span>
                    <span><?= htmlspecialchars($classroom['department']) ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    <?= htmlspecialchars($project['title']) ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                    <?= htmlspecialchars($project['description']) ?>
                </p>

                <!-- Teammates & Mentor Row -->
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold text-[10px] uppercase">Faculty Guide:</span>
                    <span class="bg-indigo-50 border border-indigo-200 text-indigo-800 font-bold px-2.5 py-1 rounded-lg">
                        <?= htmlspecialchars($project['mentor_name']) ?> (<?= htmlspecialchars($project['mentor_code']) ?>)
                    </span>

                    <span class="text-slate-400 font-semibold text-[10px] uppercase ml-2">Team Members:</span>
                    <?php foreach ($teammates as $t): ?>
                        <span class="bg-slate-100 border border-slate-200 text-slate-700 font-medium px-2.5 py-1 rounded-lg">
                            <?= htmlspecialchars($t['name']) ?> 
                            <span class="font-mono text-slate-400 text-[10px]">(<?= htmlspecialchars($t['identifier']) ?>)</span>
                            <?= $t['is_leader'] ? '<span class="text-indigo-600 font-bold text-[10px] ml-1">★</span>' : '' ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Score & Attendance Widgets -->
            <div class="flex flex-row lg:flex-col gap-3 min-w-[200px]">
                <!-- Attendance Widget -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex-1 text-center">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Cumulative Attendance</span>
                    <span class="font-mono text-xl font-bold <?= $myAttendance['has_shortage'] ? 'text-red-600' : 'text-emerald-600' ?>">
                        <?= $myAttendance['percentage'] ?>%
                    </span>
                    <span class="block text-[10px] text-slate-500 mt-0.5">
                        <?= $myAttendance['attended'] ?> / <?= $myAttendance['total_held'] ?> Saturday Reviews
                    </span>
                </div>

                <!-- CIE Score Widget -->
                <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-3 flex-1 text-center">
                    <span class="block text-[10px] uppercase font-bold text-indigo-800">CIE Score (Out of 100)</span>
                    <span class="font-mono text-xl font-bold text-indigo-900">
                        <?= $totalCie !== null ? number_format($totalCie, 1) : '--' ?>
                    </span>
                    <span class="block text-[10px] text-indigo-600 mt-0.5">
                        <?= $project['is_finalized'] ? 'Locked by Guide 🔒' : 'Continuous Evaluation' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: Saturday Review Engine Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Saturday Guide Review Schedule</h2>
                    <p class="text-xs text-slate-500">Weekly technical progress logs and faculty directives.</p>
                </div>
            </div>

            <div class="space-y-4">
                <?php foreach ($meetings as $m): ?>
                    <?php 
                    $temp = get_week_temporal_status($m['meeting_date'], $m['status']);
                    $update = !empty($m['team_update']) ? json_decode($m['team_update'], true) : null;
                    ?>
                    <div class="bg-white rounded-2xl border <?= $temp['is_current'] ? 'border-indigo-400 ring-2 ring-indigo-200' : 'border-slate-200' ?> p-6 shadow-sm">
                        <!-- Week Header -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div class="flex items-center space-x-3">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-800 font-mono font-bold text-xs rounded-lg">
                                    Week <?= $m['week_number'] ?>
                                </span>
                                <span class="text-xs font-semibold text-slate-700">
                                    Review Date: <strong class="text-slate-900"><?= date('l, d M Y', strtotime($m['meeting_date'])) ?></strong>
                                </span>
                            </div>

                            <!-- Temporal Badges -->
                            <div>
                                <?php if ($temp['is_future']): ?>
                                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full border border-slate-200 flex items-center">
                                        🔒 Locked until <?= date('d M', strtotime($temp['date_from'])) ?>
                                    </span>
                                <?php elseif ($temp['is_current']): ?>
                                    <span class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-full animate-pulse">
                                        Active Submission Week
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs font-semibold <?= $m['status'] === 'held' ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-slate-600 bg-slate-100' ?> px-3 py-1 rounded-full">
                                        Status: <?= ucfirst($m['status']) ?> (Frozen)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Review Content -->
                        <?php if ($update): ?>
                            <!-- Submitted Log Display -->
                            <div class="space-y-3 text-xs">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Technical Work Done:</span>
                                    <p class="text-slate-800 mt-1 leading-relaxed"><?= htmlspecialchars($update['work_done']) ?></p>
                                </div>

                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Planned Next Steps:</span>
                                    <p class="text-slate-800 mt-1 leading-relaxed"><?= htmlspecialchars($update['next_steps']) ?></p>
                                </div>

                                <?php if (!empty($update['blockers']) && $update['blockers'] !== 'None reported'): ?>
                                    <div class="bg-red-50 p-3 rounded-xl border border-red-200 text-red-700">
                                        <span class="block text-[10px] font-bold uppercase text-red-500">Blockers & Bottlenecks:</span>
                                        <p class="mt-1"><?= htmlspecialchars($update['blockers']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <p class="text-[10px] text-slate-400 italic">
                                    Submitted by <?= htmlspecialchars($m['submitter_name'] ?? 'Team') ?> on <?= date('d M Y, h:i A', strtotime($m['submitted_at'])) ?>
                                </p>
                            </div>
                        <?php elseif ($temp['student_can_edit']): ?>
                            <!-- Current Active Week Submission Form -->
                            <form action="submit_weekly_log.php" method="POST" class="space-y-4">
                                <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        1. Technical Work Completed This Week <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="work_done" required rows="2" placeholder="Describe tangible technical milestones achieved (hardware circuits soldered, algorithms trained, API endpoints tested)..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        2. Planned Next Steps for Upcoming Week <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="next_steps" required rows="2" placeholder="List objectives for the next review milestone..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        3. Technical Bottlenecks or Blockers (Optional)
                                    </label>
                                    <input type="text" name="blockers" placeholder="Hardware component delays, library incompatibilities, etc." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
                                    Submit Saturday Progress Log &rarr;
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs text-slate-400 italic">
                                <?= $temp['is_future'] ? '🔒 Submissions unlock on ' . date('d M Y', strtotime($temp['date_from'])) . '.' : 'No log submitted for this past meeting.' ?>
                            </div>
                        <?php endif; ?>

                        <!-- Guide Remarks & Feedback -->
                        <?php if (!empty($m['guide_feedback'])): ?>
                            <div class="mt-4 pt-3 border-t border-slate-100 p-3 bg-indigo-50/60 border border-indigo-100 rounded-xl text-xs">
                                <span class="block text-[10px] font-bold uppercase text-indigo-700">Guide Remarks:</span>
                                <p class="text-indigo-950 mt-1 italic leading-relaxed">"<?= htmlspecialchars($m['guide_feedback']) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right 1 Col: Directives, Media Upload & CIE Breakdown -->
        <div class="space-y-6">
            <!-- Actionable Mentor Directives Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900">Mentor Directives & Tasks</h3>
                    <span class="text-xs font-mono font-bold bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">
                        <?= count(array_filter($allDirectives, fn($d) => $d['status'] !== 'done')) ?> Pending
                    </span>
                </div>

                <?php if (empty($allDirectives)): ?>
                    <p class="text-xs text-slate-400 italic">No directives assigned by faculty mentor yet.</p>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach ($allDirectives as $d): ?>
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-slate-800 font-medium leading-snug"><?= htmlspecialchars($d['text']) ?></p>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider flex-shrink-0 <?= $d['status'] === 'done' ? 'bg-emerald-100 text-emerald-800' : ($d['status'] === 'acknowledged' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') ?>">
                                        <?= ucfirst($d['status']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>Week <?= $d['week_number'] ?> Review</span>
                                    <?php if ($d['status'] === 'open'): ?>
                                        <form action="manage_instruction.php" method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="instruction_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="status" value="acknowledged">
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-bold underline">
                                                Acknowledge Task &check;
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload Project Asset Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 mb-1">Upload Project Deliverable</h3>
                <p class="text-xs text-slate-500 mb-4">Add UI screenshots, prototype photos, or repository URLs for faculty showcase.</p>

                <form action="upload_project_asset.php" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
                    <input type="hidden" name="project_id" value="<?= $projectId ?>">

                    <div>
                        <label class="block font-semibold text-slate-700 uppercase text-[10px] mb-1">Asset Category</label>
                        <select name="asset_type" id="studentAssetTypeSelect" onchange="toggleAssetUploadType(this.value)" class="w-full px-2.5 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="screenshot">UI / Architecture Screenshot</option>
                            <option value="photo">Hardware Prototype Photo</option>
                            <option value="codebase">Codebase Repository (GitHub/GitLab)</option>
                            <option value="demo_link">Live Demo Video Link</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 uppercase text-[10px] mb-1">Title</label>
                        <input type="text" name="title" required placeholder="e.g. YOLOv8 Inference Output" class="w-full px-2.5 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div id="fileUploadContainer">
                        <label class="block font-semibold text-slate-700 uppercase text-[10px] mb-1">Image File (JPEG, PNG, WebP)</label>
                        <input type="file" name="asset_file" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div id="urlUploadContainer" class="hidden">
                        <label class="block font-semibold text-slate-700 uppercase text-[10px] mb-1">External Complete URL</label>
                        <input type="url" name="external_url" placeholder="https://github.com/..." class="w-full px-2.5 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 uppercase text-[10px] mb-1">Caption / Technical Notes</label>
                        <input type="text" name="caption" placeholder="Short description of what is shown..." class="w-full px-2.5 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors mt-2">
                        Publish to Gallery
                    </button>
                </form>
            </div>

            <!-- Published Assets Gallery Preview -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900">Project Asset Gallery</h3>
                    <span class="text-xs text-slate-400 font-mono"><?= count($assets) ?> items</span>
                </div>

                <?php if (empty($assets)): ?>
                    <p class="text-xs text-slate-400 italic">No media assets published yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach ($assets as $a): ?>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden text-xs">
                                <?php if (!empty($a['file_path'])): ?>
                                    <div class="h-24 bg-slate-900 cursor-pointer" onclick="openMediaLightbox('<?= htmlspecialchars($a['file_path']) ?>', '<?= htmlspecialchars(addslashes($a['title'])) ?>', '<?= htmlspecialchars($a['asset_type']) ?>', '<?= htmlspecialchars(addslashes($a['caption'] ?? '')) ?>')">
                                        <img src="<?= htmlspecialchars($a['file_path']) ?>" alt="<?= htmlspecialchars($a['title']) ?>" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'250\' viewBox=\'0 0 400 250\'><rect fill=\'%230f172a\' width=\'400\' height=\'250\'/><text fill=\'%2394a3b8\' x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'14\'>Visual Project Evidence</text></svg>';" class="w-full h-full object-cover hover:opacity-80 transition-opacity">
                                    </div>
                                <?php else: ?>
                                    <div class="h-24 bg-indigo-900 p-3 text-white flex flex-col justify-center items-center text-center">
                                        <span class="text-[10px] uppercase font-bold text-indigo-300"><?= $a['asset_type'] ?></span>
                                        <a href="<?= htmlspecialchars($a['external_url']) ?>" target="_blank" class="text-[10px] text-white underline mt-1 truncate max-w-full">
                                            Visit Link &rarr;
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="p-2">
                                    <p class="font-bold text-slate-800 truncate text-[11px]"><?= htmlspecialchars($a['title']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Includes -->
<?php include __DIR__ . '/media_modal.php'; ?>

<script>
function toggleAssetUploadType(val) {
    const fileContainer = document.getElementById('fileUploadContainer');
    const urlContainer  = document.getElementById('urlUploadContainer');

    if (val === 'codebase' || val === 'demo_link') {
        fileContainer.classList.add('hidden');
        urlContainer.classList.remove('hidden');
    } else {
        fileContainer.classList.remove('hidden');
        urlContainer.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
