<?php
/**
 * Explicit $viewData contract — lean academic version.
 * Single source of truth for every key the dashboard views consume.
 */

const VIEW_KEYS = [
    'actualView'           => ['string', 'Student'],
    'classroom_id'         => ['int', 0],
    'classroomRole'        => ['string', 'Team Member'],
    'isTeacherDrilldown'   => ['bool', false],
    'isCoordinator'        => ['bool', false],
    'isLeaderView'         => ['bool', false],
    'myProject'            => ['?array', null],
    'myProjectId'          => ['?int', null],
    'teamRoster'           => ['array', []],
    'actualTeamRoster'     => ['array', []],
    'maxTeamSize'          => ['int', 4],
    'pendingRequests'      => ['array', []],
    'unassignedClassmates' => ['array', []],
    'meetings'             => ['array', []],
    'personalAttendance'   => ['array', ['total' => 0, 'present' => 0, 'percentage' => 0.0, 'has_shortage' => false]],
    'evaluationSheet'      => ['?array', null],
    'canEvaluateProject'   => ['bool', false],
    'availableProjects'    => ['array', []],
    'classroomName'        => ['string', ''],
    'inviteCode'           => ['string', ''],
    'classroomRoster'      => ['array', []],
    'projectGroups'        => ['array', []],
    'availableMentors'     => ['array', []],
];

const VIEW_CONTRACTS = [
    '_shell'      => ['actualView', 'classroom_id', 'classroomRole', 'isTeacherDrilldown', 'teamRoster'],
    '_project'    => ['myProjectId', 'myProject', 'actualTeamRoster', 'meetings', 'personalAttendance',
                      'isCoordinator', 'isLeaderView', 'evaluationSheet', 'canEvaluateProject',
                      'maxTeamSize', 'pendingRequests', 'unassignedClassmates'],
    'Student'     => ['_shell', '_project'],
    'Marketplace' => ['_shell', 'availableProjects'],
    'Teacher'     => ['_shell', 'classroomName', 'inviteCode', 'classroomRoster', 'projectGroups',
                      'availableMentors', 'isCoordinator'],
];

if (!function_exists('view_contract_keys')) {
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
    function enforce_view_contract(array $viewData): array
    {
        $view = (string)($viewData['actualView'] ?? 'Student');
        foreach (view_contract_keys($view) as $key) {
            if (!array_key_exists($key, $viewData)) {
                $viewData[$key] = VIEW_KEYS[$key][1] ?? null;
            }
        }
        return $viewData;
    }
}
