<?php
// Project-scoped data (weekly logs) for Student / Leader / drilldown views.

require_once __DIR__ . '/../repositories/meeting_repository.php';

if (isset($viewData['myProjectId'])) {
    $projectId = (int)$viewData['myProjectId'];
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    // Ensure Saturday weekly meetings exist for this project
    $meetings = meeting_ensure_project_meetings(
        $pdo,
        $projectId,
        $viewData['classroomStartDate'] ?? null,
        $viewData['classroomEndDate'] ?? null
    );

    $viewData['weeklyMeetings'] = [];
    foreach ($meetings as $m) {
        $viewData['weeklyMeetings'][(int)$m['week_number']] = $m;
    }

    // Calculate personal attendance metrics for current student/user
    $viewData['personalAttendance'] = meeting_calculate_student_attendance(
        $pdo,
        $projectId,
        $currentUserId
    );

    // Fetch prior open instructions for rollover to current/future meetings
    $viewData['rolloverInstructions'] = guide_instructions_get_open_by_project($pdo, $projectId);

    // Can current user submit a weekly update for this project?
    $classroomId = (int)($viewData['classroom_id'] ?? 0);
    $viewData['canSubmitWeeklyUpdate'] = is_active_project_member($pdo, $projectId, $currentUserId, $classroomId);

    // Fetch Weekly Logs (legacy bridge)
    $viewData['weeklyLogs'] = [];
    if (!empty($viewData['classroomStartDate']) && !empty($viewData['classroomEndDate'])) {
        $stmtLogs = $pdo->prepare("
            SELECT ws.*, wr.status as review_status, wr.mentor_remarks, wr.reviewed_at
            FROM weekly_submissions ws
            LEFT JOIN weekly_reviews wr ON ws.id = wr.submission_id
            WHERE ws.project_id = ?
        ");
        $stmtLogs->execute([$projectId]);
        $logsRaw = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        $filesBySub = [];
        $attBySub = [];
        if (!empty($logsRaw)) {
            $subIds = array_column($logsRaw, 'id');
            $ph = implode(',', array_fill(0, count($subIds), '?'));

            $stmtFiles = $pdo->prepare("SELECT submission_id, file_name, file_path FROM weekly_submission_files WHERE submission_id IN ($ph)");
            $stmtFiles->execute($subIds);
            foreach ($stmtFiles->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $filesBySub[$f['submission_id']][] = ['file_name' => $f['file_name'], 'file_path' => $f['file_path']];
            }

            $stmtAtt = $pdo->prepare("
                SELECT wa.submission_id, wa.user_id, wa.present, u.username
                FROM weekly_attendance wa
                JOIN users u ON wa.user_id = u.id
                WHERE wa.submission_id IN ($ph)
            ");
            $stmtAtt->execute($subIds);
            foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $a) {
                $attBySub[$a['submission_id']][] = [
                    'user_id' => $a['user_id'],
                    'present' => $a['present'],
                    'status'  => $a['present'] ? 'present' : 'absent',
                    'username' => $a['username']
                ];
            }
        }

        foreach ($logsRaw as $log) {
            $log['files'] = $filesBySub[$log['id']] ?? [];
            $log['attendance'] = $attBySub[$log['id']] ?? [];
            $viewData['weeklyLogs'][$log['week_number']] = $log;
        }
    }

    // Merge meeting engine records into weeklyLogs
    foreach ($viewData['weeklyMeetings'] as $wk => $m) {
        $teamUpdate = $m['team_update_parsed'];
        $mFiles = $m['files'] ?? [];
        $mInstructions = $m['instructions'] ?? [];
        $mFeedbackHistory = $m['feedback_history'] ?? [];

        if (!isset($viewData['weeklyLogs'][$wk])) {
            $viewData['weeklyLogs'][$wk] = [
                'id'                 => $m['id'],
                'meeting_id'         => $m['id'],
                'project_id'         => $m['project_id'],
                'week_number'        => $wk,
                'meeting_date'       => $m['meeting_date'],
                'meeting_status'     => $m['status'],
                'status'             => $m['status'],
                'work_summary'       => $teamUpdate['work_done'],
                'next_steps'         => $teamUpdate['next_steps'],
                'blockers'           => $teamUpdate['blockers'],
                'submitted_by'       => $m['submitted_by'],
                'submitted_by_name'  => $m['submitted_by_name'] ?? null,
                'submitted_at'       => $m['submitted_at'],
                'mentor_remarks'     => $m['guide_feedback'],
                'review_status'      => ($m['status'] === 'held' ? 'approved' : ($m['team_update'] ? 'pending' : 'unsubmitted')),
                'attendance'         => $m['attendance'] ?? [],
                'attendance_changes' => $m['attendance_changes'] ?? [],
                'files'              => $mFiles,
                'instructions'       => $mInstructions,
                'feedback_history'   => $mFeedbackHistory,
            ];
        } else {
            $viewData['weeklyLogs'][$wk]['meeting_id'] = $m['id'];
            $viewData['weeklyLogs'][$wk]['meeting_status'] = $m['status'];
            $viewData['weeklyLogs'][$wk]['meeting_date'] = $m['meeting_date'];
            $viewData['weeklyLogs'][$wk]['submitted_by'] = $m['submitted_by'] ?? ($viewData['weeklyLogs'][$wk]['submitted_by'] ?? null);
            $viewData['weeklyLogs'][$wk]['submitted_by_name'] = $m['submitted_by_name'] ?? ($viewData['weeklyLogs'][$wk]['submitted_by_name'] ?? null);
            $viewData['weeklyLogs'][$wk]['submitted_at'] = $m['submitted_at'] ?? ($viewData['weeklyLogs'][$wk]['submitted_at'] ?? null);
            $viewData['weeklyLogs'][$wk]['attendance_changes'] = $m['attendance_changes'] ?? [];
            if (!empty($teamUpdate['blockers'])) {
                $viewData['weeklyLogs'][$wk]['blockers'] = $teamUpdate['blockers'];
            }
            if (empty($viewData['weeklyLogs'][$wk]['work_summary']) && !empty($teamUpdate['work_done'])) {
                $viewData['weeklyLogs'][$wk]['work_summary'] = $teamUpdate['work_done'];
            }
            if (empty($viewData['weeklyLogs'][$wk]['next_steps']) && !empty($teamUpdate['next_steps'])) {
                $viewData['weeklyLogs'][$wk]['next_steps'] = $teamUpdate['next_steps'];
            }
            if (!empty($m['attendance'])) {
                $viewData['weeklyLogs'][$wk]['attendance'] = $m['attendance'];
            }
            if (!empty($m['guide_feedback'])) {
                $viewData['weeklyLogs'][$wk]['mentor_remarks'] = $m['guide_feedback'];
            }
            // Merge files: meeting files take precedence, preserve legacy if present
            $existingFiles = $viewData['weeklyLogs'][$wk]['files'] ?? [];
            $combinedFiles = array_merge($existingFiles, $mFiles);
            // Deduplicate files by file_path
            $uniqueFiles = [];
            $seenPaths = [];
            foreach ($combinedFiles as $cf) {
                $fp = $cf['file_path'] ?? '';
                if ($fp !== '' && !isset($seenPaths[$fp])) {
                    $seenPaths[$fp] = true;
                    $uniqueFiles[] = $cf;
                }
            }
            $viewData['weeklyLogs'][$wk]['files'] = $uniqueFiles;
            $viewData['weeklyLogs'][$wk]['instructions'] = $mInstructions;
            $viewData['weeklyLogs'][$wk]['feedback_history'] = $mFeedbackHistory;
        }
    }

    // Build simplified team roster
    $viewData['teamRoster'] = [];
    if (!empty($viewData['actualTeamRoster'])) {
        foreach ($viewData['actualTeamRoster'] as $member) {
            $viewData['teamRoster'][] = [
                'id'   => $member['id'],
                'name' => $member['username'],
                'role' => $member['is_leader'] ? 'Leader' : 'Member',
            ];
        }
    }

    // Phase 6: Continuous Internal Evaluation (CIE) Marks Sheet
    require_once __DIR__ . '/../repositories/marks_repository.php';
    $viewData['canEvaluateProject'] = can_evaluate_project($pdo, $projectId, $currentUserId);
    $viewData['evaluationSheet'] = marks_get_full_evaluation_sheet($pdo, $projectId);

    // Individual evaluation data for the currently logged-in student
    $viewData['studentEvaluation'] = null;
    if (!empty($viewData['evaluationSheet']['students'])) {
        foreach ($viewData['evaluationSheet']['students'] as $stu) {
            if ((int)$stu['user_id'] === $currentUserId) {
                $viewData['studentEvaluation'] = $stu;
                break;
            }
        }
    }
}

