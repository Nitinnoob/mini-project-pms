<?php
/**
 * Milestone Widget Partial.
 * Displays classroom milestones and countdown timers.
 * Presentation-layer only - no SQL.
 */
$milestones = $viewData['classroomMilestones'] ?? [];
?>
<div class="card p-4 flex flex-col gap-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded flex items-center justify-center text-xs" style="background: var(--overlay-medium); color: var(--accent);">
                <i class="fas fa-flag"></i>
            </div>
            <h3 class="font-head font-semibold text-sm">Classroom Milestones & Targets</h3>
        </div>
        <span class="text-xs text-muted-ui font-mono-ui"><?php echo count($milestones); ?> milestones</span>
    </div>

    <?php if (empty($milestones)): ?>
        <p class="text-xs text-muted-ui py-2 italic">No formal milestones published yet for this classroom.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
            <?php foreach ($milestones as $m): ?>
                <?php
                $isOverdue = !empty($m['is_overdue']);
                $isDueToday = !empty($m['is_due_today']);
                $isUpcoming = !empty($m['is_upcoming']);

                $borderClass = $isDueToday ? 'border-amber-500/50' : ($isOverdue ? 'border-ui' : 'border-ui');
                $bgClass = $isDueToday ? 'bg-amber-950/20' : 'bg-panel';
                $badgeTone = $m['badge_class'] ?? 'badge-muted';
                ?>
                <div class="p-3 rounded border <?php echo $borderClass; ?> <?php echo $bgClass; ?> flex flex-col justify-between gap-2 transition hover-overlay-subtle">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <span class="text-xs font-semibold leading-snug line-clamp-2"><?php echo e($m['title']); ?></span>
                        </div>
                        <div class="text-[11px] text-muted-ui font-mono-ui flex items-center gap-1.5">
                            <i class="far fa-calendar text-[10px]"></i>
                            <span><?php echo e($m['formatted_due_date']); ?></span>
                        </div>
                    </div>
                    <div class="pt-1 border-t border-ui/50 flex items-center justify-between">
                        <span class="badge <?php echo $badgeTone; ?> text-[10px] font-mono-ui font-medium">
                            <?php if ($isDueToday): ?>
                                <i class="fas fa-exclamation-circle mr-1"></i>
                            <?php elseif ($isOverdue): ?>
                                <i class="fas fa-check-circle mr-1 text-muted-ui"></i>
                            <?php else: ?>
                                <i class="fas fa-clock mr-1"></i>
                            <?php endif; ?>
                            <?php echo e($m['countdown_label']); ?>
                        </span>
                        <?php if ($isUpcoming && $m['days_remaining'] > 0): ?>
                            <span class="text-[10px] text-muted-ui font-mono-ui">T-<?php echo (int)$m['days_remaining']; ?>d</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
