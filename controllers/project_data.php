<?php
// Project-scoped data for Student / Teacher Drilldown views.

require_once __DIR__ . '/../repositories/meeting_repository.php';

if (isset($viewData['myProjectId'])) {
    $projectId = (int)$viewData['myProjectId'];
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    // Fetch all meetings for this project
    $viewData['meetings'] = meeting_get_by_project($pdo, $projectId);

    // Calculate personal attendance for the current user
    $viewData['personalAttendance'] = meeting_calculate_attendance($pdo, $projectId, $currentUserId);

    // CIE Marks (only loaded, never shown to students — views handle visibility)
    require_once __DIR__ . '/../repositories/marks_repository.php';
    $viewData['canEvaluateProject'] = can_evaluate_project($pdo, $projectId, $currentUserId);
    $viewData['evaluationSheet'] = marks_get_full_evaluation_sheet($pdo, $projectId);

    // Build team roster with attendance %
    $viewData['teamRoster'] = [];
    if (!empty($viewData['actualTeamRoster'])) {
        foreach ($viewData['actualTeamRoster'] as $member) {
            $mId = (int)$member['id'];
            $att = meeting_calculate_attendance($pdo, $projectId, $mId);

            $viewData['teamRoster'][] = [
                'id'             => $mId,
                'name'           => $member['username'],
                'role'           => $member['is_leader'] ? 'Leader' : 'Member',
                'attendance_pct' => ($att['total'] > 0) ? $att['percentage'] : null,
                'attendance'     => $att,
            ];
        }
    }
}
