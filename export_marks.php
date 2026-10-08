<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'repositories/marks_repository.php';
require_once 'views/helpers.php';

require_login();

$classroomId = (int)($_GET['classroom_id'] ?? 0);
$projectId   = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
$format      = strtolower(trim((string)($_GET['format'] ?? 'print')));

if ($classroomId <= 0) {
    $_SESSION['flash_error'] = 'Invalid classroom ID for marks export.';
    header("Location: hub.php");
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = classroom_role($pdo, $classroomId, $currentUserId);

if ($userRole === null) {
    http_response_code(403);
    $_SESSION['flash_error'] = 'You do not have access to this classroom.';
    header("Location: hub.php");
    exit;
}

// Fetch classroom details
$stmtCls = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmtCls->execute([$classroomId]);
$classroom = $stmtCls->fetch(PDO::FETCH_ASSOC);

if (!$classroom) {
    http_response_code(404);
    $_SESSION['flash_error'] = 'Classroom not found.';
    header("Location: hub.php");
    exit;
}

// Retrieve sheets data
if ($projectId !== null && $projectId > 0) {
    // Single project export
    $sheets = [marks_get_full_evaluation_sheet($pdo, $projectId)];
} else {
    // Whole classroom export
    $sheets = marks_get_classroom_evaluation_sheet($pdo, $classroomId);
}

// CSV Export
if ($format === 'csv') {
    $filename = 'pms_marks_sheet_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $classroom['name']) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Header
    fputcsv($out, [
        'Sl No',
        'USN',
        'Student Name',
        'Role',
        'Project Title',
        'Assigned Guide',
        'Attendance %',
        'Present',
        'Absent',
        'Excused',
        'Meetings Held',
        'Attendance Shortage Alert',
        'Report Marks (Max 50)',
        'Presentation Marks (Max 25)',
        'Viva Q&A Marks (Max 25)',
        'Total Marks (Max 100)',
        'Percentage',
        'Evaluation Status',
        'Finalized At',
    ]);

    $sl = 1;
    foreach ($sheets as $sheet) {
        $projName   = $sheet['project']['name'];
        $guideName  = $sheet['project']['mentor_name'] ?? 'Unassigned';
        $isFin      = $sheet['is_finalized'];
        $finAt      = $sheet['project_marks']['finalized_at'] ?? 'Draft';
        $statusStr  = $isFin ? 'Finalized' : 'Draft / In Progress';

        foreach ($sheet['students'] as $stu) {
            $att = $stu['attendance'];
            fputcsv($out, [
                $sl++,
                $stu['usn'],
                $stu['username'],
                $stu['role_label'],
                $projName,
                $guideName,
                $stu['attendance_pct'] . '%',
                $att['present'],
                $att['absent'],
                $att['excused'],
                $att['held'],
                $stu['is_shortage'] ? 'YES (<75%)' : 'NO',
                $stu['report_marks'] !== null ? $stu['report_marks'] : '',
                $stu['presentation_marks'] !== null ? $stu['presentation_marks'] : '',
                $stu['qa_marks'] !== null ? $stu['qa_marks'] : '',
                $stu['total_marks'] !== null ? $stu['total_marks'] : '',
                $stu['percentage'] !== null ? $stu['percentage'] . '%' : '',
                $statusStr,
                $finAt,
            ]);
        }
    }

    fclose($out);
    exit;
}

// Printable HTML View
$config = marks_get_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks & Attendance Sheet - <?php echo e($classroom['name']); ?></title>
    <style>
        :root {
            --bg-sheet: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --border-dark: #334155;
            --accent: #2563eb;
            --shortage-bg: #fee2e2;
            --shortage-text: #991b1b;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8fafc;
            color: var(--text-main);
            margin: 0;
            padding: 24px;
            font-size: 13px;
        }

        .sheet-container {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--bg-sheet);
            padding: 32px 40px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }

        .no-print-bar {
            max-width: 1100px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background: #0284c7;
            color: #ffffff;
        }
        .btn-primary:hover { background: #0369a1; }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover { background: #f1f5f9; }

        .header-block {
            text-align: center;
            border-bottom: 2px solid var(--border-dark);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .dept-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin: 0 0 10px 0;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            font-size: 12px;
            text-align: left;
            margin-top: 12px;
            background: #f8fafc;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .meta-grid div { margin: 2px 0; }
        .meta-label { font-weight: 600; color: #475569; }

        table.marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 12px;
        }

        table.marks-table th,
        table.marks-table td {
            border: 1px solid var(--border-color);
            padding: 8px 10px;
            text-align: left;
        }

        table.marks-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        table.marks-table th.center,
        table.marks-table td.center {
            text-align: center;
        }

        table.marks-table th.right,
        table.marks-table td.right {
            text-align: right;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .row-leader { font-weight: 600; }
        .badge-shortage {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 3px;
            background: var(--shortage-bg);
            color: var(--shortage-text);
        }

        .badge-fin {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 3px;
            background: #dcfce7;
            color: #166534;
        }

        .badge-draft {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 3px;
            background: #f1f5f9;
            color: #475569;
        }

        .signatures-container {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            page-break-inside: avoid;
        }

        .sig-line {
            width: 80%;
            margin: 0 auto;
            border-top: 1px solid var(--border-dark);
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar { display: none !important; }
            .sheet-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            @page {
                size: A4 landscape;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <a href="dashboard.php?classroom_id=<?php echo urlencode((string)$classroomId); ?>" class="btn btn-secondary">
                &larr; Back to Dashboard
            </a>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$classroomId); ?><?php echo $projectId ? '&project_id=' . urlencode((string)$projectId) : ''; ?>&format=csv" class="btn btn-secondary">
                Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                Print Marks Sheet
            </button>
        </div>
    </div>

    <div class="sheet-container">
        <div class="header-block">
            <h1 class="dept-title">Department of Computer Science & Engineering</h1>
            <h2 class="doc-title">Continuous Internal Evaluation (CIE) & Project Marks Sheet</h2>
            
            <div class="meta-grid">
                <div><span class="meta-label">Classroom:</span> <?php echo e($classroom['name']); ?></div>
                <div><span class="meta-label">Generated On:</span> <?php echo date('d-M-Y H:i'); ?></div>
                <div><span class="meta-label">Grading Scheme:</span> 50 Report / 25 Pres / 25 Q&A (100 Total)</div>
            </div>
        </div>

        <table class="marks-table">
            <thead>
                <tr>
                    <th class="center" style="width: 35px;">#</th>
                    <th style="width: 100px;">USN</th>
                    <th>Student Name</th>
                    <th>Project Title</th>
                    <th>Guide</th>
                    <th class="center" style="width: 90px;">Attendance</th>
                    <th class="right" style="width: 65px;">Report<br>(/50)</th>
                    <th class="right" style="width: 65px;">Pres.<br>(/25)</th>
                    <th class="right" style="width: 65px;">Q&A<br>(/25)</th>
                    <th class="right" style="width: 65px;">Total<br>(/100)</th>
                    <th class="center" style="width: 75px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sl = 1;
                $hasStudents = false;
                foreach ($sheets as $sheet):
                    $projName  = $sheet['project']['name'];
                    $guideName = $sheet['project']['mentor_name'] ?? 'Unassigned';
                    $isFin     = $sheet['is_finalized'];
                    foreach ($sheet['students'] as $stu):
                        $hasStudents = true;
                ?>
                <tr class="<?php echo $stu['is_leader'] ? 'row-leader' : ''; ?>">
                    <td class="center"><?php echo $sl++; ?></td>
                    <td style="font-family: monospace;"><?php echo e($stu['usn']); ?></td>
                    <td>
                        <?php echo e($stu['username']); ?>
                        <?php if ($stu['is_leader']): ?><span style="font-size: 10px; color: #0284c7; font-weight: bold;">(Leader)</span><?php endif; ?>
                    </td>
                    <td><?php echo e($projName); ?></td>
                    <td><?php echo e($guideName); ?></td>
                    <td class="center">
                        <span style="font-weight: 600;"><?php echo $stu['attendance_pct']; ?>%</span>
                        <?php if ($stu['is_shortage']): ?>
                            <br><span class="badge-shortage">Shortage</span>
                        <?php endif; ?>
                    </td>
                    <td class="right"><?php echo $stu['report_marks'] !== null ? number_format((float)$stu['report_marks'], 1) : '-'; ?></td>
                    <td class="right"><?php echo $stu['presentation_marks'] !== null ? number_format((float)$stu['presentation_marks'], 1) : '-'; ?></td>
                    <td class="right"><?php echo $stu['qa_marks'] !== null ? number_format((float)$stu['qa_marks'], 1) : '-'; ?></td>
                    <td class="right" style="font-weight: 700; color: #0f172a;">
                        <?php echo $stu['total_marks'] !== null ? number_format((float)$stu['total_marks'], 1) : '-'; ?>
                    </td>
                    <td class="center">
                        <?php if ($isFin): ?>
                            <span class="badge-fin">Finalized</span>
                        <?php else: ?>
                            <span class="badge-draft">Draft</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    endforeach;
                endforeach;
                if (!$hasStudents):
                ?>
                <tr>
                    <td colspan="11" class="center" style="padding: 24px; color: var(--text-muted);">
                        No student evaluation records found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signatures-container">
            <div>
                <div class="sig-line">Project Guide Signature</div>
            </div>
            <div>
                <div class="sig-line">Project Coordinator Signature</div>
            </div>
            <div>
                <div class="sig-line">Head of Department (HOD) Signature</div>
            </div>
        </div>
    </div>

</body>
</html>
