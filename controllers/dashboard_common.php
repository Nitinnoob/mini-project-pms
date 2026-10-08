<?php
// Shared state for every view: progress defaults, calendar grid, phase schedule, heatmap default.
// 1. Global project progress (shown in every layout, styled per mode)
$viewData['isNewlyCreated'] = ($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') && count($viewData['actualTeamRoster'] ?? []) <= 1;
if (!isset($viewData['progressPercent'])) { $viewData['progressPercent'] = 0; }
if ($viewData['progressPercent'] === 0) {
    $viewData['progressStatus'] = 'ontrack';
    $viewData['progressWord'] = 'project initialized';
} elseif ($viewData['progressPercent'] < 50) {
    $viewData['progressStatus'] = 'behind';
    $viewData['progressWord'] = 'falling behind pace';
} elseif ($viewData['progressPercent'] >= 80) {
    $viewData['progressStatus'] = 'ahead';
    $viewData['progressWord'] = 'ahead of pace';
} else {
    $viewData['progressStatus'] = 'ontrack';
    $viewData['progressWord'] = 'on pace for Friday';
}

// 2. Calendar and Team Data
$calYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$calMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
if ($calMonth < 1) { $calMonth = 12; $calYear--; }
if ($calMonth > 12) { $calMonth = 1; $calYear++; }

$calDate = new DateTime(sprintf('%04d-%02d-01', $calYear, $calMonth));
$daysInMonth = (int) $calDate->format('t');
$startWeekday = (int) $calDate->format('N');
$todayNum = (int) date('j');
$isCurrentMonth = ($calYear === (int)date('Y') && $calMonth === (int)date('n'));

$viewData['monthLabel'] = $calDate->format('F Y');
$viewData['isCurrentMonth'] = $isCurrentMonth;
$viewData['todayNum'] = $todayNum;
$viewData['startWeekday'] = $startWeekday;
$viewData['daysInMonth'] = $daysInMonth;

// Generate prev/next month navigation URLs
$prevMonth = $calMonth - 1; $prevYear = $calYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $calMonth + 1; $nextYear = $calYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$baseCalUrl = "dashboard.php?classroom_id=" . urlencode($viewData['classroom_id']) . (!empty($viewData['myProjectId']) ? "&project_id=" . $viewData['myProjectId'] : "");
$viewData['prevMonthUrl'] = $baseCalUrl . "&month=$prevMonth&year=$prevYear&view=calendar";
$viewData['nextMonthUrl'] = $baseCalUrl . "&month=$nextMonth&year=$nextYear&view=calendar";
$viewData['activeBoardView'] = (isset($_GET['view']) && $_GET['view'] === 'calendar') ? 'calendar' : 'kanban';

$viewData['calendarTasks'] = [];
$viewData['teamRoster'] = [];
$viewData['tasks'] = ['todo' => [], 'inprogress' => [], 'done' => []];
$viewData['issueCount'] = 0;
$viewData['blockerCount'] = 0;
$viewData['onTrackCount'] = 0;
$viewData['avgVelocity'] = 0;
$viewData['daysToDeadline'] = '-';
$viewData['deadlineLabel'] = 'no deadline set';
$viewData['openIssues'] = [];
$viewData['canResolveIssues'] = false;
$viewData['activity_log'] = [];
$viewData['deliverables'] = [];

    // ---- Phase 4: build the auto-derived week/phase list (1 phase = 1 week) ----
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
    $viewData['tasksHaveWeekColumn'] = phase_tasks_column_exists($pdo);
    // Active log weeks exclude merged-away weeks (they fold into their target).
    $viewData['logWeeks'] = array_values(array_filter(
        $viewData['phases'],
        fn($p) => !$p['is_merged']
    ));


// 5. Contribution heatmap (10 weeks x 7 days) from real activity_log counts.
// Cell index = week * 7 + day, oldest first; the last cell is today.
$heatmapWeeks = 10;
$heatmapPattern = array_fill(0, $heatmapWeeks * 7, 0);
