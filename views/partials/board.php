<?php
// Shared Kanban Board & Calendar View partial (used by Student & Project Leader dashboards)
require_once __DIR__ . '/../helpers.php';
$isCalendar = ($viewData['activeBoardView'] ?? 'kanban') === 'calendar';
// Column config: DOM ids are kept stable for assets/js/dashboard.js drag-and-drop.
$boardColumns = [
    'todo'       => ['label' => 'To do',       'listId' => 'todo-list',       'colClass' => '',                  'badge' => 'pms-badge bg-raised',
                     'icon' => 'fa-list',        'emptyTitle' => 'No tasks to do yet',      'emptyText' => 'Add your first task to get started!'],
    'inprogress' => ['label' => 'In progress', 'listId' => 'inprogress-list', 'colClass' => 'pms-col-accent-2', 'badge' => 'badge badge-accent-2',
                     'icon' => 'fa-sync-alt',    'emptyTitle' => 'No tasks in progress',    'emptyText' => 'Start working on tasks to see them appear here.'],
    'done'       => ['label' => 'Done',        'listId' => 'done-list',       'colClass' => 'pms-col-accent',   'badge' => 'badge badge-accent',
                     'icon' => 'fa-check-double','emptyTitle' => 'No tasks completed yet',  'emptyText' => 'Finish tasks to see your progress here.'],
];
?>
<div id="kanban-view" class="view-pane grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 <?php echo $isCalendar ? 'hidden' : ''; ?>">
    <?php foreach ($boardColumns as $cardVariant => $col): $colTasks = $viewData['tasks'][$cardVariant] ?? []; ?>
    <div class="card p-4 flex flex-col <?php echo $col['colClass']; ?>">
        <h3 class="font-semibold mb-3 flex justify-between font-head"><?php echo $col['label']; ?> <span class="<?php echo $col['badge']; ?>"><?php echo count($colTasks); ?></span></h3>
        <div id="<?php echo $col['listId']; ?>" class="flex-1 space-y-3 min-h-[200px]">
            <?php if (empty($colTasks)): ?>
            <div class="text-center text-muted-ui py-8">
                <i class="fas <?php echo $col['icon']; ?> text-muted-ui text-2xl mb-3"></i>
                <p class="font-medium"><?php echo $col['emptyTitle']; ?></p>
                <p class="text-sm"><?php echo $col['emptyText']; ?></p>
            </div>
            <?php else: ?>
                <?php foreach ($colTasks as $task) { require __DIR__ . '/task_card.php'; } ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Calendar Month View -->
<div id="calendar-view" class="view-pane card p-4 flex-1 <?php echo !$isCalendar ? 'hidden' : ''; ?>">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-3">
            <a href="<?php echo e($viewData['prevMonthUrl'] ?? '#'); ?>" class="cal-nav-btn btn-ui px-2.5 py-1 text-xs border border-ui rounded hover:border-accent transition" title="Previous Month">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h3 class="font-semibold font-head"><?php echo e($viewData['monthLabel'] ?? ''); ?></h3>
            <a href="<?php echo e($viewData['nextMonthUrl'] ?? '#'); ?>" class="cal-nav-btn btn-ui px-2.5 py-1 text-xs border border-ui rounded hover:border-accent transition" title="Next Month">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="flex items-center gap-3 text-xs text-muted-ui font-mono-ui">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--danger);"></span>high</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--accent-2);"></span>normal</span>
        </div>
    </div>
    <div class="grid grid-cols-7 gap-2 text-center text-xs text-muted-ui font-semibold mb-2 font-mono-ui">
        <div>mon</div><div>tue</div><div>wed</div><div>thu</div><div>fri</div><div>sat</div><div>sun</div>
    </div>
    <div class="grid grid-cols-7 gap-2">
        <?php
        for ($b = 1; $b < ($viewData['startWeekday'] ?? 1); $b++) echo '<div class="cal-cell"></div>';
        for ($d = 1; $d <= ($viewData['daysInMonth'] ?? 30); $d++) {
            $isToday = (!empty($viewData['isCurrentMonth']) && $d === ($viewData['todayNum'] ?? 0));
            echo '<div class="cal-cell pms-card p-1.5 flex flex-col ' . ($isToday ? 'is-today' : '') . '">';
            echo '<span class="text-xs font-semibold font-mono-ui ' . ($isToday ? 'text-accent-2' : 'text-muted-ui') . '">' . $d . '</span><div class="mt-1 space-y-1">';
            if (isset($viewData['calendarTasks'][$d])) {
                foreach ($viewData['calendarTasks'][$d] as $t) {
                    $bg = ($t['priority'] ?? 'normal') === 'high' ? 'rgba(var(--danger-rgb), 0.18)' : 'rgba(var(--accent-2-rgb), 0.18)';
                    $fg = ($t['priority'] ?? 'normal') === 'high' ? 'var(--danger)' : 'var(--accent-2)';
                    echo '<div class="cal-pill" style="background:' . $bg . '; color:' . $fg . ';" title="' . e($t['title'] ?? '') . '">' . e($t['title'] ?? '') . '</div>';
                }
            }
            echo '</div></div>';
        }
        ?>
    </div>
</div>
