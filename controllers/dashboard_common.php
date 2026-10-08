<?php
// Shared state for every view: phase schedule and project status defaults.

$viewData['isNewlyCreated'] = ($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') && count($viewData['actualTeamRoster'] ?? []) <= 1;

// Phase schedule: build the auto-derived week/phase list (1 phase = 1 week)
// Done here in the controller so views never query.
phase_seed($pdo, $viewData['classroom_id'], $viewData['classroomStartDate'], $viewData['classroomEndDate']);
$viewData['phases'] = phase_build_list(
    $viewData['classroomStartDate'],
    $viewData['classroomEndDate'],
    phase_load_overrides($pdo, $viewData['classroom_id'])
);
$viewData['totalWeeks'] = count($viewData['phases']);
$currentPhase = null;
foreach ($viewData['phases'] as $p) {
    if ($p['is_current']) { $currentPhase = $p; break; }
}
$viewData['currentPhase'] = $currentPhase;
$viewData['hasSchedule'] = ($viewData['totalWeeks'] > 0);

// Active log weeks exclude merged-away weeks (they fold into their target).
$viewData['logWeeks'] = array_values(array_filter(
    $viewData['phases'],
    fn($p) => !$p['is_merged']
));

// Phase 5: Classroom Milestones with countdowns
require_once __DIR__ . '/../repositories/milestone_repository.php';
$viewData['classroomMilestones'] = classroom_milestones_get_all($pdo, (int)$viewData['classroom_id']);

// Defaults
$viewData['teamRoster'] = [];
