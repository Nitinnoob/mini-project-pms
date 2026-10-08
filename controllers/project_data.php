<?php
// Project-scoped data (tasks, health, logs, issues, heatmap) for Student / Leader / drilldown views.

    // REAL DB LOGIC
    if (isset($viewData['myProjectId'])) {
        // Phase 4: select week_number when the migration has been applied,
        // otherwise fall back to the pre-migration column set.
        $weekSelect = $viewData['tasksHaveWeekColumn'] ? ', t.week_number' : '';
        $stmtTasks = $pdo->prepare("SELECT t.*, u.username as assignee_name $weekSelect FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.created_at DESC");
        $stmtTasks->execute([$viewData['myProjectId']]);
        $allTasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        $totalTasks = 0;
        $doneTasksCount = 0;
        $viewData['tasksByWeek'] = [];
        $viewData['weekStats'] = [];

        foreach ($viewData['phases'] as $p) {
            $viewData['weekStats'][$p['week_number']] = ['total' => 0, 'done' => 0, 'percent' => 0];
        }

        foreach ($allTasks as $t) {
            // Bind each task to a phase/week. Prefer an explicit week_number;
            // otherwise derive it from due_date so legacy rows still land in a
            // phase instead of falling out of every weekly rollup.
            if (!array_key_exists('week_number', $t)) {
                $t['week_number'] = null;
            }
            if (empty($t['week_number'])) {
                $derived = phase_for_due_date(
                    $viewData['classroomStartDate'],
                    $viewData['classroomEndDate'],
                    $t['due_date'] ?? null
                );
                $t['week_number'] = $derived;
            }
            $t['week_label'] = null;
            foreach ($viewData['phases'] as $p) {
                if ($p['week_number'] == $t['week_number']) {
                    $t['week_label'] = $p['label'];
                    break;
                }
            }
            // A task in a merged week also rolls up into the week it merged into.
            $rollupWeeks = [$t['week_number']];
            foreach ($viewData['phases'] as $p) {
                if ($p['is_merged'] && $p['week_number'] == $t['week_number']) {
                    $rollupWeeks[] = (int)$p['merged_into_week'];
                }
            }

            $viewData['tasks'][$t['status']][] = $t;
            $totalTasks++;
            if ($t['status'] === 'done') $doneTasksCount++;

            foreach ($rollupWeeks as $rw) {
                if ($rw === null) continue;
                if (!isset($viewData['tasksByWeek'][$rw])) $viewData['tasksByWeek'][$rw] = [];
                $viewData['tasksByWeek'][$rw][] = $t;

                if (isset($viewData['weekStats'][$rw])) {
                    $viewData['weekStats'][$rw]['total']++;
                    if ($t['status'] === 'done') $viewData['weekStats'][$rw]['done']++;
                }
            }

            if (!empty($t['due_date'])) {
                try {
                    $due = new DateTime($t['due_date']);
                    if ($due->format('Y-m') === $calDate->format('Y-m')) {
                        $viewData['calendarTasks'][(int)$due->format('j')][] = [
                            'title' => $t['title'],
                            'priority' => $t['priority']
                        ];
                    }
                } catch (Exception $e) {}
            }
        }

        foreach ($viewData['weekStats'] as $wk => $st) {
            $viewData['weekStats'][$wk]['percent'] = $st['total'] > 0
                ? (int)round(($st['done'] / $st['total']) * 100)
                : 0;
        }
        $viewData['scheduledTaskCount'] = count(array_filter($allTasks, fn($t) => !empty($t['week_number'])));

        if ($totalTasks > 0) {
            $viewData['progressPercent'] = (int)round(($doneTasksCount / $totalTasks) * 100);
            $viewData['avgVelocity'] = $viewData['progressPercent'];
        }

        // Health dots: red = has overdue tasks, amber = a task due within 48h,
        // green = on pace. Done tasks never count against a member.
        $todayDt = new DateTime('today');
        $soonDt  = (clone $todayDt)->modify('+2 days');

        if (!empty($viewData['actualTeamRoster'])) {
            foreach ($viewData['actualTeamRoster'] as $member) {
                $userTasks = 0;
                $userDone = 0;
                $overdue = 0;
                $dueSoon = 0;
                foreach ($allTasks as $t) {
                    if ($t['assigned_to'] == $member['id']) {
                        $userTasks++;
                        if ($t['status'] === 'done') {
                            $userDone++;
                        } elseif (!empty($t['due_date'])) {
                            try {
                                $dueD = new DateTime($t['due_date']);
                                if ($dueD < $todayDt) $overdue++;
                                elseif ($dueD <= $soonDt) $dueSoon++;
                            } catch (Exception $e) {}
                        }
                    }
                }
                $userPercent = ($userTasks > 0) ? (int)round(($userDone / $userTasks) * 100) : 0;

                if ($overdue > 0) {
                    $health = 'red';
                    $healthNote = $overdue . ' overdue task' . ($overdue > 1 ? 's' : '');
                } elseif ($dueSoon > 0) {
                    $health = 'amber';
                    $healthNote = $dueSoon . ' task' . ($dueSoon > 1 ? 's' : '') . ' due within 48 hours';
                } else {
                    $health = 'green';
                    $healthNote = 'On pace';
                }

                $viewData['teamRoster'][] = [
                    'name' => $member['username'],
                    'role' => $member['is_leader'] ? 'Leader' : 'Member',
                    'status' => $health,
                    'status_note' => $healthNote,
                    'task' => ($userTasks > 0) ? "$userDone / $userTasks tasks done" : 'No tasks',
                    'percent' => $userPercent
                ];
            }
        }
        $viewData['onTrackCount'] = count(array_filter($viewData['teamRoster'], fn($m) => $m['status'] === 'green'));

        // Days to deadline: the next phase deadline that is not yet past; once every
        // phase has ended, fall back to the classroom end date (and report overdue).
        $deadlineTarget = null;
        foreach ($viewData['phases'] as $p) {
            if (!$p['is_merged'] && !empty($p['date_to']) && new DateTime($p['date_to']) >= $todayDt) {
                $deadlineTarget = $p['date_to'];
                break;
            }
        }
        if ($deadlineTarget === null && !empty($viewData['classroomEndDate'])) {
            $deadlineTarget = $viewData['classroomEndDate'];
        }
        if ($deadlineTarget !== null) {
            $daysDiff = (int)$todayDt->diff(new DateTime($deadlineTarget))->format('%r%a');
            $viewData['daysToDeadline'] = abs($daysDiff);
            $viewData['deadlineLabel'] = $daysDiff < 0 ? 'days overdue' : ($daysDiff === 0 ? 'due today' : 'days to deadline');
        } else {
            $viewData['daysToDeadline'] = '-';
            $viewData['deadlineLabel'] = 'no deadline set';
        }

        // Fetch Weekly Logs
        $viewData['weeklyLogs'] = [];
        if (!empty($viewData['classroomStartDate']) && !empty($viewData['classroomEndDate'])) {
            $stmtLogs = $pdo->prepare("
                SELECT ws.*, wr.status as review_status, wr.mentor_remarks, wr.reviewed_at
                FROM weekly_submissions ws
                LEFT JOIN weekly_reviews wr ON ws.id = wr.submission_id
                WHERE ws.project_id = ?
            ");
            $stmtLogs->execute([$viewData['myProjectId']]);
            $logsRaw = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($logsRaw)) {
                $subIds = array_column($logsRaw, 'id');
                $ph = implode(',', array_fill(0, count($subIds), '?'));

                // Batch files — avoids one query per submission.
                $filesBySub = [];
                $stmtFiles = $pdo->prepare("SELECT submission_id, file_name, file_path FROM weekly_submission_files WHERE submission_id IN ($ph)");
                $stmtFiles->execute($subIds);
                foreach ($stmtFiles->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    $filesBySub[$f['submission_id']][] = ['file_name' => $f['file_name'], 'file_path' => $f['file_path']];
                }

                // Batch attendance — avoids one query per submission.
                $attBySub = [];
                $stmtAtt = $pdo->prepare("
                    SELECT wa.submission_id, wa.user_id, wa.present, u.username
                    FROM weekly_attendance wa
                    JOIN users u ON wa.user_id = u.id
                    WHERE wa.submission_id IN ($ph)
                ");
                $stmtAtt->execute($subIds);
                foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $a) {
                    $attBySub[$a['submission_id']][] = ['user_id' => $a['user_id'], 'present' => $a['present'], 'username' => $a['username']];
                }
            }

            foreach ($logsRaw as $log) {
                $log['files'] = $filesBySub[$log['id']] ?? [];
                $log['attendance'] = $attBySub[$log['id']] ?? [];
                $viewData['weeklyLogs'][$log['week_number']] = $log;
            }
        }

        // Fetch Activity Log for Sidebar Feed
        $stmtAct = $pdo->prepare("
            SELECT a.action, a.details, a.created_at, COALESCE(u.username, 'Deleted user') AS username
            FROM activity_log a 
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.project_id = ? 
            ORDER BY a.created_at DESC LIMIT 20
        ");
        $stmtAct->execute([$viewData['myProjectId']]);
        $viewData['activity_log'] = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Deliverables for Sidebar Drawer
        $stmtDel = $pdo->prepare("
            SELECT d.file_name, d.file_path, d.uploaded_at, COALESCE(u.username, 'Deleted user') AS uploader_name
            FROM deliverables d 
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.project_id = ? 
            ORDER BY d.uploaded_at DESC
        ");
        $stmtDel->execute([$viewData['myProjectId']]);
        $viewData['deliverables'] = $stmtDel->fetchAll(PDO::FETCH_ASSOC);

        // Open blockers (Escalation flare). Count drives the "pending issues" stat.
        try {
            $stmtIssues = $pdo->prepare("
                SELECT i.id, i.title, i.description, i.severity, i.week_number, i.created_at,
                       COALESCE(u.username, 'Deleted user') AS raised_by_name,
                       t.title AS task_title
                FROM issues i
                LEFT JOIN users u ON i.raised_by = u.id
                LEFT JOIN tasks t ON i.task_id = t.id
                WHERE i.project_id = ? AND i.status = 'open'
                ORDER BY FIELD(i.severity, 'critical', 'high', 'medium', 'low'), i.created_at DESC
            ");
            $stmtIssues->execute([$viewData['myProjectId']]);
            $viewData['openIssues'] = $stmtIssues->fetchAll(PDO::FETCH_ASSOC);
            foreach ($viewData['openIssues'] as &$iss) {
                $iss['phase_label'] = null;
                foreach ($viewData['phases'] as $p) {
                    if (!empty($iss['week_number']) && $p['week_number'] == $iss['week_number']) {
                        $iss['phase_label'] = $p['label'];
                        break;
                    }
                }
            }
            unset($iss);
        } catch (Throwable $e) {
            $viewData['openIssues'] = []; // issues table not on the Phase 5 schema yet
        }
        $viewData['blockerCount'] = count($viewData['openIssues']);

        // Who may mark blockers resolved: this project's leader, its mentor, or the coordinator.
        $viewData['canResolveIssues'] = ($viewData['actualView'] === 'Project Leader');
        if (!empty($viewData['isTeacherDrilldown'])) {
            $viewData['canResolveIssues'] = $viewData['isCoordinator']
                || ((int)($viewData['myProject']['mentor_id'] ?? 0) === (int)$_SESSION['user_id']);
        }
    }

if (isset($viewData['myProjectId'])) {
    $heatDays = $heatmapWeeks * 7;
    $stmtHeat = $pdo->prepare("
        SELECT DATE(created_at) AS d, COUNT(*) AS c
        FROM activity_log
        WHERE project_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
    ");
    $stmtHeat->execute([$viewData['myProjectId'], $heatDays - 1]);
    $heatCounts = [];
    foreach ($stmtHeat->fetchAll(PDO::FETCH_ASSOC) as $hr) {
        $heatCounts[$hr['d']] = (int)$hr['c'];
    }
    $heatToday = new DateTime('today');
    for ($i = 0; $i < $heatDays; $i++) {
        $dayKey = (clone $heatToday)->modify('-' . ($heatDays - 1 - $i) . ' days')->format('Y-m-d');
        $heatmapPattern[$i] = $heatCounts[$dayKey] ?? 0;
    }
}
