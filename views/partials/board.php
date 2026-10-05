<?php
// Shared Kanban Board & Calendar View partial (used by Student & Project Leader dashboards)
$isCalendar = ($viewData['activeBoardView'] ?? 'kanban') === 'calendar';

// Phase 4: task cards show which derived phase/week they belong to.
// An unscheduled task is called out rather than silently bucketed.
function task_phase_chip($task)
{
    if (!empty($task['week_label'])) {
        $label = htmlspecialchars($task['week_label']);
        $title = 'Scheduled in this phase';
        return "<span class=\"phase-chip\" style=\"background: var(--accent-2); color: var(--bg);\" title=\"$title\">$label</span>";
    }
    return '<span class="phase-chip" style="background: transparent; border: 1px dashed var(--border); color: var(--muted);" title="This task has no due date or phase yet">Unscheduled</span>';
}
?>
<div id="kanban-view" class="view-pane grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 <?php echo $isCalendar ? 'hidden' : ''; ?>">
    <!-- To Do Column -->
    <div class="card p-4 flex flex-col">
        <h3 class="font-semibold mb-3 flex justify-between font-head">To do <span class="bg-raised border border-ui px-2 text-xs py-0.5" style="border-radius: var(--radius);"><?php echo count($viewData['tasks']["todo"] ?? []); ?></span></h3>
        <div id="todo-list" class="flex-1 space-y-3 min-h-[200px]">
            <?php if (empty($viewData['tasks']['todo'] ?? [])): ?>
            <div class="text-center text-muted-ui py-8">
                <i class="fas fa-list text-muted-ui text-2xl mb-3"></i>
                <p class="font-medium">No tasks to do yet</p>
                <p class="text-sm">Add your first task to get started!</p>
            </div>
            <?php else: ?>
                <?php foreach (($viewData['tasks']['todo'] ?? []) as $task): ?>
                <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);" data-task-id="<?php echo $task['id']; ?>">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                        <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                    </div>
                    <div class="mb-1"><?php echo task_phase_chip($task); ?></div>
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-sm font-semibold pr-2"><?php echo htmlspecialchars($task['title']); ?></p>
                        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                        <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- In Progress Column -->
    <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent-2);">
        <h3 class="font-semibold mb-3 flex justify-between font-head">In progress <span class="badge badge-accent-2"><?php echo count($viewData['tasks']["inprogress"] ?? []); ?></span></h3>
        <div id="inprogress-list" class="flex-1 space-y-3 min-h-[200px]">
            <?php if (empty($viewData['tasks']['inprogress'] ?? [])): ?>
            <div class="text-center text-muted-ui py-8">
                <i class="fas fa-sync-alt text-muted-ui text-2xl mb-3"></i>
                <p class="font-medium">No tasks in progress</p>
                <p class="text-sm">Start working on tasks to see them appear here.</p>
            </div>
            <?php else: ?>
                <?php foreach (($viewData['tasks']['inprogress'] ?? []) as $task): ?>
                <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius); border-left: 2px solid var(--accent-2);" data-task-id="<?php echo $task['id']; ?>">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                        <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                    </div>
                    <div class="mb-1"><?php echo task_phase_chip($task); ?></div>
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-sm font-semibold pr-2"><?php echo htmlspecialchars($task['title']); ?></p>
                        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                        <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Done Column -->
    <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent);">
        <h3 class="font-semibold mb-3 flex justify-between font-head">Done <span class="badge badge-accent"><?php echo count($viewData['tasks']["done"] ?? []); ?></span></h3>
        <div id="done-list" class="flex-1 space-y-3 min-h-[200px]">
            <?php if (empty($viewData['tasks']['done'] ?? [])): ?>
            <div class="text-center text-muted-ui py-8">
                <i class="fas fa-check-double text-muted-ui text-2xl mb-3"></i>
                <p class="font-medium">No tasks completed yet</p>
                <p class="text-sm">Finish tasks to see your progress here.</p>
            </div>
            <?php else: ?>
                <?php foreach (($viewData['tasks']['done'] ?? []) as $task): ?>
                <div class="bg-raised border border-ui p-3 cursor-grab opacity-60" style="border-radius: var(--radius);" data-task-id="<?php echo $task['id']; ?>">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                        <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                    </div>
                    <div class="mb-1"><?php echo task_phase_chip($task); ?></div>
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-sm font-semibold pr-2 line-through"><?php echo htmlspecialchars($task['title']); ?></p>
                        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                        <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Calendar Month View -->
<div id="calendar-view" class="view-pane card p-4 flex-1 <?php echo !$isCalendar ? 'hidden' : ''; ?>">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-3">
            <a href="<?php echo htmlspecialchars($viewData['prevMonthUrl'] ?? '#'); ?>" class="cal-nav-btn btn-ui px-2.5 py-1 text-xs border border-ui rounded hover:border-accent transition" title="Previous Month">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h3 class="font-semibold font-head"><?php echo htmlspecialchars($viewData['monthLabel'] ?? ''); ?></h3>
            <a href="<?php echo htmlspecialchars($viewData['nextMonthUrl'] ?? '#'); ?>" class="cal-nav-btn btn-ui px-2.5 py-1 text-xs border border-ui rounded hover:border-accent transition" title="Next Month">
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
            echo '<div class="cal-cell bg-raised border border-ui p-1.5 flex flex-col ' . ($isToday ? 'is-today' : '') . '" style="border-radius: var(--radius);">';
            echo '<span class="text-xs font-semibold font-mono-ui ' . ($isToday ? 'text-accent-2' : 'text-muted-ui') . '">' . $d . '</span><div class="mt-1 space-y-1">';
            if (isset($viewData['calendarTasks'][$d])) {
                foreach ($viewData['calendarTasks'][$d] as $t) {
                    $bg = ($t['priority'] ?? 'normal') === 'high' ? 'rgba(var(--danger-rgb), 0.18)' : 'rgba(var(--accent-2-rgb), 0.18)';
                    $fg = ($t['priority'] ?? 'normal') === 'high' ? 'var(--danger)' : 'var(--accent-2)';
                    echo '<div class="cal-pill" style="background:' . $bg . '; color:' . $fg . ';" title="' . htmlspecialchars($t['title'] ?? '') . '">' . htmlspecialchars($t['title'] ?? '') . '</div>';
                }
            }
            echo '</div></div>';
        }
        ?>
    </div>
</div>
