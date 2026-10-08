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
    'tasks'                => ['array', ['todo' => [], 'inprogress' => [], 'done' => []]],
    'teamRoster'           => ['array', []],
    'actualTeamRoster'     => ['array', []],
    'maxTeamSize'          => ['int', 0],
    'pendingRequests'      => ['array', []],
    'unassignedClassmates' => ['array', []],
    'avgVelocity'          => ['float', 0.0],
    'blockerCount'         => ['int', 0],
    'daysToDeadline'       => ['?int', null],
    'deadlineLabel'        => ['string', ''],
    'activity_log'         => ['array', []],
    'deliverables'         => ['array', []],
    // Issues
    'openIssues'           => ['array', []],
    'canResolveIssues'     => ['bool', false],
    // Weekly logs
    'weeklyLogs'           => ['array', []],
    'logWeeks'             => ['array', []],
    'weekStats'            => ['array', []],
    // Board / calendar
    'activeBoardView'      => ['string', 'kanban'],
    'calendarTasks'        => ['array', []],
    'daysInMonth'          => ['int', 30],
    'startWeekday'         => ['int', 1],
    'isCurrentMonth'       => ['bool', false],
    'todayNum'             => ['int', 0],
    'monthLabel'           => ['string', ''],
    'prevMonthUrl'         => ['string', '#'],
    'nextMonthUrl'         => ['string', '#'],
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
    '_shell'         => ['actualView', 'classroom_id', 'isTeacherDrilldown', 'isNewlyCreated', 'teamRoster'],
    '_project'       => ['myProjectId', 'myProject', 'phases', 'tasks', 'actualTeamRoster', 'activeBoardView',
                         'calendarTasks', 'daysInMonth', 'startWeekday', 'isCurrentMonth', 'todayNum',
                         'monthLabel', 'prevMonthUrl', 'nextMonthUrl', 'openIssues', 'canResolveIssues',
                         'weeklyLogs', 'logWeeks', 'weekStats', 'hasSchedule', 'isCoordinator', 'isLeaderView',
                         'activity_log', 'deliverables'],
    'Student'        => ['_shell', '_project'],
    'Project Leader' => ['_shell', '_project', 'avgVelocity', 'blockerCount', 'daysToDeadline', 'deadlineLabel',
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
