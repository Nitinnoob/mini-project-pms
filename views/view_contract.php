<?php
/**
 * Explicit $viewData contract.
 *
 * Single source of truth for every key the dashboard views consume, its type,
 * and its safe default. Controllers populate $viewData; enforce_view_contract()
 * then (1) logs any key a rendered view needs but the controller forgot, and
 * (2) back-fills a typed default so views never hit undefined-index / null
 * TypeErrors. Removing a calculation from a controller is now visible in the
 * error log instead of silently breaking a template.
 *
 * Presentation-layer only - no SQL.
 */

// key => [type, default]. Types: array|bool|int|float|string|?array|?int|?string|?float
const VIEW_KEYS = [
    // Shell / context
    'actualView'           => ['string', 'Student'],
    'classroom_id'         => ['int', 0],
    'isTeacherDrilldown'   => ['bool', false],
    'isNewlyCreated'       => ['bool', false],
    'isCoordinator'        => ['bool', false],
    'isLeaderView'         => ['bool', false],
    'hasSchedule'          => ['bool', false],
    'phases'               => ['array', []],
    // Project
    'myProject'            => ['?array', null],
    'myProjectId'          => ['?int', null],
    'teamRoster'           => ['array', []],
    'actualTeamRoster'     => ['array', []],
    'maxTeamSize'          => ['int', 0],
    'pendingRequests'      => ['array', []],
    'unassignedClassmates' => ['array', []],
    // Weekly logs & meetings
    'weeklyLogs'           => ['array', []],
    'weeklyMeetings'       => ['array', []],
    'personalAttendance'   => ['array', [
        'percentage'       => 100.0,
        'present'          => 0,
        'absent'           => 0,
        'excused'          => 0,
        'holiday'          => 0,
        'rescheduled'      => 0,
        'held'             => 0,
        'total_meetings'   => 0,
        'evaluated'        => 0,
        'is_shortage'      => false,
    ]],
    'logWeeks'               => ['array', []],
    'rolloverInstructions'   => ['array', []],
    'canSubmitWeeklyUpdate'  => ['bool', false],
    'classroomMilestones'  => ['array', []],
    // Evaluation Marks (Phase 6)
    'evaluationSheet'      => ['?array', null],
    'studentEvaluation'    => ['?array', null],
    'canEvaluateProject'   => ['bool', false],
    // Marketplace
    'availableProjects'    => ['array', []],
    // Teacher
    'classroomName'        => ['string', ''],
    'inviteCode'           => ['string', ''],
    'classroomStartDate'   => ['?string', null],
    'classroomEndDate'     => ['?string', null],
    'totalWeeks'           => ['int', 0],
    'classroomRoster'      => ['array', []],
    'projectGroups'        => ['array', []],
    'availableMentors'     => ['array', []],
];

// Keys each view (incl. its partials/modals) requires, per actualView.
const VIEW_CONTRACTS = [
    '_shell'         => ['actualView', 'classroom_id', 'isTeacherDrilldown', 'isNewlyCreated', 'teamRoster', 'classroomMilestones'],
    '_project'       => ['myProjectId', 'myProject', 'phases', 'actualTeamRoster',
                         'weeklyLogs', 'weeklyMeetings', 'personalAttendance', 'logWeeks', 'rolloverInstructions', 'canSubmitWeeklyUpdate', 'hasSchedule', 'isCoordinator', 'isLeaderView', 'evaluationSheet', 'studentEvaluation', 'canEvaluateProject'],
    'Student'        => ['_shell', '_project'],
    'Project Leader' => ['_shell', '_project',
                         'maxTeamSize', 'pendingRequests', 'unassignedClassmates'],
    'Marketplace'    => ['_shell', 'availableProjects'],
    'Teacher'        => ['_shell', 'classroomName', 'inviteCode', 'classroomStartDate', 'classroomEndDate',
                         'totalWeeks', 'classroomRoster', 'projectGroups', 'availableMentors', 'phases',
                         'hasSchedule', 'isCoordinator'],
];

if (!function_exists('view_contract_keys')) {
    /** Resolve the flat list of required keys for a view (expands _groups). */
    function view_contract_keys(string $view): array
    {
        $keys = [];
        foreach (VIEW_CONTRACTS[$view] ?? VIEW_CONTRACTS['_shell'] as $k) {
            $keys = array_merge($keys, str_starts_with($k, '_') ? VIEW_CONTRACTS[$k] : [$k]);
        }
        return array_values(array_unique($keys));
    }
}

if (!function_exists('enforce_view_contract')) {
    /**
     * Validate & back-fill $viewData for the active view.
     * Missing keys are logged and defaulted; existing values are left untouched.
     */
    function enforce_view_contract(array $viewData): array
    {
        $view    = (string) ($viewData['actualView'] ?? 'Student');
        $missing = [];
        foreach (view_contract_keys($view) as $key) {
            if (!array_key_exists($key, $viewData)) {
                $missing[] = $key;
                $viewData[$key] = VIEW_KEYS[$key][1] ?? null;
            }
        }
        if ($missing) {
            error_log(sprintf('[viewData contract] %s view missing keys: %s', $view, implode(', ', $missing)));
        }
        return $viewData;
    }
}
