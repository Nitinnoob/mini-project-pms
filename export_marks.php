<?php
// export_marks.php - Printable A4 Landscape Department Ledger & CSV Export
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/meeting_engine.php';

require_login();
$user = current_user();

$classroomId = (int)($_GET['classroom_id'] ?? ($_SESSION['active_classroom_id'] ?? 0));

if ($classroomId <= 0) {
    set_flash('error', 'Please select a classroom to generate the marks ledger.');
    header('Location: hub.php');
    exit;
}

// Fetch classroom details
$cStmt = $pdo->prepare("SELECT c.*, u.name as coordinator_name FROM classrooms c JOIN users u ON c.teacher_id = u.id WHERE c.id = ?");
$cStmt->execute([$classroomId]);
$classroom = $cStmt->fetch();

if (!$classroom) {
    set_flash('error', 'Classroom not found.');
    header('Location: hub.php');
    exit;
}

// Fetch all projects, members, guides, marks and attendance
$query = "
    SELECT 
        p.id as project_id,
        p.title as project_title,
        mentor.name as mentor_name,
        mentor.identifier as mentor_id_code,
        u.id as student_id,
        u.name as student_name,
        u.identifier as usn,
        pm.is_leader,
        pmarks.report_marks,
        pmarks.is_finalized,
        smarks.presentation_marks,
        smarks.qa_marks
    FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    JOIN users u ON pm.student_id = u.id
    JOIN users mentor ON p.mentor_id = mentor.id
    LEFT JOIN project_marks pmarks ON p.id = pmarks.project_id
    LEFT JOIN student_marks smarks ON (p.id = smarks.project_id AND u.id = smarks.student_id)
    WHERE p.classroom_id = ?
    ORDER BY p.id ASC, pm.is_leader DESC, u.identifier ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$classroomId]);
$rows = $stmt->fetchAll();

// Calculate attendance for all projects in this classroom
$projectAttendanceCache = [];
$records = [];

foreach ($rows as $r) {
    $pId = $r['project_id'];
    if (!isset($projectAttendanceCache[$pId])) {
        $projectAttendanceCache[$pId] = calculate_project_attendance($pdo, $pId);
    }

    $attData = $projectAttendanceCache[$pId][$r['student_id']] ?? null;
    $attPercent = $attData ? $attData['percentage'] : 100.0;
    $hasShortage = $attData ? $attData['has_shortage'] : false;

    $report = $r['report_marks'] !== null ? (float)$r['report_marks'] : 0.0;
    $pres   = $r['presentation_marks'] !== null ? (float)$r['presentation_marks'] : 0.0;
    $qa     = $r['qa_marks'] !== null ? (float)$r['qa_marks'] : 0.0;
    $total  = $report + $pres + $qa;

    $records[] = array_merge($r, [
        'attendance_percentage' => $attPercent,
        'has_shortage'          => $hasShortage,
        'total_marks'           => $total,
    ]);
}

// Check for CSV Export mode
$format = $_GET['format'] ?? 'html';

if ($format === 'csv') {
    $filename = 'CIE_Marks_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $classroom['name']) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Institution Header Lines
    fputcsv($output, [$classroom['institution']]);
    fputcsv($output, [$classroom['department']]);
    fputcsv($output, ['CLASSROOM: ' . $classroom['name'], 'INVITE CODE: ' . $classroom['invite_code']]);
    fputcsv($output, ['CONTINUOUS INTERNAL EVALUATION (CIE) MARKS LEDGER']);
    fputcsv($output, []); // Blank line

    // Column Headers
    fputcsv($output, [
        'Sl No',
        'USN',
        'Student Name',
        'Role',
        'Project Title',
        'Faculty Guide',
        'Attendance %',
        'Attendance Status',
        'Report (50)',
        'Presentation (25)',
        'Viva Q&A (25)',
        'Total (100)',
        'Status'
    ]);

    $sl = 1;
    foreach ($records as $rec) {
        fputcsv($output, [
            $sl++,
            $rec['usn'],
            $rec['student_name'],
            $rec['is_leader'] ? 'Leader' : 'Member',
            $rec['project_title'],
            $rec['mentor_name'],
            $rec['attendance_percentage'] . '%',
            $rec['has_shortage'] ? 'SHORTAGE (<75%)' : 'Eligible',
            number_format((float)$rec['report_marks'], 2),
            number_format((float)$rec['presentation_marks'], 2),
            number_format((float)$rec['qa_marks'], 2),
            number_format((float)$rec['total_marks'], 2),
            $rec['is_finalized'] ? 'FINALIZED' : 'DRAFT',
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIE Marks Ledger - <?= htmlspecialchars($classroom['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ledger-container { box-shadow: none !important; border: none !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8">
    <!-- Action Bar -->
    <div class="max-w-7xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-4 no-print">
        <a href="dashboard.php" class="inline-flex items-center text-xs font-semibold text-indigo-700 hover:text-indigo-900 bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-sm">
            &larr; Return to Dashboard
        </a>
        <div class="flex items-center space-x-3">
            <a href="export_marks.php?classroom_id=<?= $classroom['id'] ?>&format=csv" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download CSV Export
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Official A4 Ledger
            </button>
        </div>
    </div>

    <!-- Official Ledger Document Container -->
    <div class="ledger-container max-w-7xl mx-auto bg-white border border-slate-300 rounded-2xl shadow-xl p-8 sm:p-10">
        <!-- Dynamic College Letterhead Header -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-black uppercase tracking-tight text-slate-950">
                <?= htmlspecialchars($classroom['institution']) ?>
            </h1>
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-normal mt-0.5">
                <?= htmlspecialchars($classroom['department']) ?>
            </h2>
            <div class="mt-2 text-xs font-semibold uppercase tracking-wider text-slate-600">
                Continuous Internal Evaluation (CIE) Marks Ledger &bull; Academic Year <?= date('Y') ?>
            </div>
            <div class="mt-2 inline-flex flex-wrap items-center justify-center gap-4 text-xs text-slate-700 bg-slate-50 px-4 py-1.5 rounded-lg border border-slate-200">
                <span><strong>Cohort:</strong> <?= htmlspecialchars($classroom['name']) ?></span>
                <span>&bull;</span>
                <span><strong>Section Code:</strong> <span class="font-mono"><?= htmlspecialchars($classroom['invite_code']) ?></span></span>
                <span>&bull;</span>
                <span><strong>Coordinator:</strong> <?= htmlspecialchars($classroom['coordinator_name']) ?></span>
                <span>&bull;</span>
                <span><strong>Period:</strong> <?= date('d M Y', strtotime($classroom['start_date'])) ?> to <?= date('d M Y', strtotime($classroom['end_date'])) ?></span>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-900 text-white font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-8">Sl</th>
                        <th class="py-2.5 px-3 border border-slate-700 w-28">USN</th>
                        <th class="py-2.5 px-3 border border-slate-700">Student Name</th>
                        <th class="py-2.5 px-3 border border-slate-700">Project Title</th>
                        <th class="py-2.5 px-3 border border-slate-700">Guide</th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center">Att %</th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-16">Report<br><span class="text-[9px] font-normal">(Max 50)</span></th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-16">Pres<br><span class="text-[9px] font-normal">(Max 25)</span></th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-16">Viva<br><span class="text-[9px] font-normal">(Max 25)</span></th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-16 bg-slate-950 font-bold">Total<br><span class="text-[9px] font-normal">(100)</span></th>
                        <th class="py-2.5 px-2 border border-slate-700 text-center w-16">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="11" class="py-8 text-center text-slate-500 italic">No registered project teams or marks found in this classroom.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sl = 1; foreach ($records as $r): ?>
                            <tr class="hover:bg-slate-50 <?= $r['has_shortage'] ? 'bg-amber-50/50' : '' ?>">
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono"><?= $sl++ ?></td>
                                <td class="py-2 px-3 border border-slate-200 font-mono font-bold text-slate-900"><?= htmlspecialchars($r['usn']) ?></td>
                                <td class="py-2 px-3 border border-slate-200 font-medium">
                                    <?= htmlspecialchars($r['student_name']) ?>
                                    <?php if ($r['is_leader']): ?>
                                        <span class="text-[10px] text-indigo-700 bg-indigo-50 border border-indigo-200 rounded px-1 ml-1 font-semibold">Leader</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-3 border border-slate-200 text-slate-800 text-[11px] leading-tight">
                                    <?= htmlspecialchars($r['project_title']) ?>
                                </td>
                                <td class="py-2 px-3 border border-slate-200 text-slate-700 whitespace-nowrap text-[11px]">
                                    <?= htmlspecialchars($r['mentor_name']) ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono">
                                    <span class="<?= $r['has_shortage'] ? 'text-red-600 font-bold' : 'text-slate-800' ?>">
                                        <?= number_format($r['attendance_percentage'], 1) ?>%
                                    </span>
                                    <?php if ($r['has_shortage']): ?>
                                        <span class="block text-[8px] text-red-600 font-extrabold uppercase">Shortage</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono">
                                    <?= $r['report_marks'] !== null ? number_format((float)$r['report_marks'], 1) : '-' ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono">
                                    <?= $r['presentation_marks'] !== null ? number_format((float)$r['presentation_marks'], 1) : '-' ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono">
                                    <?= $r['qa_marks'] !== null ? number_format((float)$r['qa_marks'], 1) : '-' ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center font-mono font-bold bg-slate-50 text-indigo-900 text-sm">
                                    <?= number_format((float)$r['total_marks'], 1) ?>
                                </td>
                                <td class="py-2 px-2 border border-slate-200 text-center text-[10px]">
                                    <?php if ($r['is_finalized']): ?>
                                        <span class="text-emerald-700 font-bold bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200">Locked</span>
                                    <?php else: ?>
                                        <span class="text-amber-700 font-medium bg-amber-50 px-1 py-0.5 rounded border border-amber-200">Draft</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Assessment Scheme Footnote -->
        <div class="mt-6 pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between text-[11px] text-slate-500">
            <div>
                <strong>Scheme Breakdown:</strong> Report & Documentation: 50 Marks (Shared) &bull; Presentation & Demo: 25 Marks &bull; Viva Voce & Q&A: 25 Marks (Total 100 Marks).
            </div>
            <div>
                <strong>Attendance Rule:</strong> Minimum 75% attendance mandatory for CIE marks approval.
            </div>
        </div>

        <!-- Official Sign-off Blocks -->
        <div class="mt-16 pt-8 border-t border-slate-300 grid grid-cols-3 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-slate-400 w-48 mx-auto mb-2 pb-8"></div>
                <p class="font-bold text-slate-900">Project Guide</p>
                <p class="text-[10px] text-slate-500">Signature & Date</p>
            </div>
            <div>
                <div class="border-b border-slate-400 w-48 mx-auto mb-2 pb-8"></div>
                <p class="font-bold text-slate-900">Project Coordinator</p>
                <p class="text-[10px] text-slate-500">Signature & Date</p>
            </div>
            <div>
                <div class="border-b border-slate-400 w-48 mx-auto mb-2 pb-8"></div>
                <p class="font-bold text-slate-900">Head of Department (HOD)</p>
                <p class="text-[10px] text-slate-500">Seal & Signature</p>
            </div>
        </div>
    </div>
</body>
</html>
