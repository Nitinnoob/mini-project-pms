<?php
/**
 * Weekly guide-meeting attendance tracker.
 *
 * Expects $viewData['attendanceWeeks'] as an ordered array of:
 *   ['week' => 1, 'status' => 'present' | 'absent' | 'pending']
 * 'pending' = week hasn't happened yet / no review logged.
 *
 * $viewData['attendancePercent']   — computed over weeks that have already occurred
 * $viewData['currentAbsentStreak'] — consecutive most-recent 'absent' weeks
 *
 * Derive these in your controller from mentor_review_modal.php's saved
 * attendance[user_id] data, filtered to the logged-in student's id, joined
 * against the project's weekly log rows (same source teacher.php/leader.php
 * already use for $totalWeeks).
 */
$weeks = $viewData['attendanceWeeks'] ?? [];
$percent = $viewData['attendancePercent'] ?? 0;
$streak = $viewData['currentAbsentStreak'] ?? 0;
$atRisk = $percent < 80; // matches "duly marks will be deducted" threshold — tune to your dept's actual cutoff
$attendedCount = count(array_filter($weeks, fn($w) => $w['status'] === 'present'));
$loggedCount = count(array_filter($weeks, fn($w) => $w['status'] !== 'pending'));
?>
<div class="card p-5" style="border-top: 2px solid <?php echo $atRisk ? 'var(--danger)' : 'var(--accent)'; ?>;">
    <div class="flex justify-between items-baseline mb-3">
        <h3 class="font-semibold text-sm font-head">
            <i class="fas fa-calendar-check me-2" style="color: var(--accent);"></i>Guide meeting attendance
        </h3>
        <span class="text-sm font-mono-ui">
            <span class="font-bold text-lg"><?php echo $attendedCount; ?></span>
            <span class="text-muted-ui">/<?php echo $loggedCount; ?> Saturdays</span>
        </span>
    </div>

    <div class="flex gap-1.5 flex-wrap">
        <?php foreach ($weeks as $w):
            $color = match ($w['status']) {
                'present' => 'var(--status-green)',
                'absent'  => 'var(--danger)',
                default   => 'var(--border)',
            };
            $label = ucfirst($w['status']);
        ?>
        <div class="w-8 h-8 rounded flex items-center justify-center text-[10px] font-mono-ui font-semibold flex-shrink-0"
             style="background: color-mix(in srgb, <?php echo $color; ?> 18%, transparent); border: 1px solid <?php echo $color; ?>; color: <?php echo $color; ?>;"
             title="Week <?php echo $w['week']; ?>: <?php echo $label; ?>">
            <?php echo $w['week']; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-3 pt-3 border-t border-ui flex items-center justify-between">
        <span class="text-xs text-muted-ui font-mono-ui"><?php echo $percent; ?>% attendance</span>
        <?php if ($streak >= 2): ?>
            <span class="badge badge-danger text-[10px]">
                <i class="fas fa-exclamation-triangle me-1"></i><?php echo $streak; ?> weeks missed in a row
            </span>
        <?php elseif ($atRisk): ?>
            <span class="badge badge-danger text-[10px]">Below 80% — marks may be deducted</span>
        <?php else: ?>
            <span class="badge badge-green text-[10px]">On track</span>
        <?php endif; ?>
    </div>
</div>
