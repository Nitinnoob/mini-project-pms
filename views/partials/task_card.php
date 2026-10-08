<?php
/**
 * Reusable Kanban task card.
 * Expects: $task (array), $cardVariant ('todo'|'inprogress'|'done'), $viewData.
 */
$cardVariant = $cardVariant ?? 'todo';
$isDone      = $cardVariant === 'done';
$cardClasses = 'pms-card p-3 cursor-grab';
$cardClasses .= $isDone ? ' opacity-60' : ' hover:opacity-90 transition shadow-sm';
if ($cardVariant === 'inprogress') $cardClasses .= ' pms-card-accent-2';
$taskId   = (int) ($task['id'] ?? 0);
$assignee = !empty($task['assigned_to']) ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned';
?>
<div class="<?php echo $cardClasses; ?>" data-task-id="<?php echo $taskId; ?>">
    <div class="flex justify-between items-start mb-2">
        <span class="pms-chip"><?php echo e($assignee ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if (($task['priority'] ?? '') === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
    </div>
    <div class="mb-1"><?php echo task_phase_chip($task); ?></div>
    <div class="flex justify-between items-start mb-1">
        <p class="text-sm font-semibold pr-2<?php echo $isDone ? ' line-through' : ''; ?>"><?php echo e($task['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
        <button onclick="openUploadModal(<?php echo $taskId; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
        <?php endif; ?>
    </div>
</div>
