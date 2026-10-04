<?php
/**
 * Report readiness gauge + deadline countdown.
 *
 * Expects:
 *   $viewData['reportDeadline']     — 'Y-m-d', e.g. '2026-10-31'
 *   $viewData['reportSections']     — [['name' => 'Abstract', 'done' => true], ...]
 *
 * Report % is derived here from the section checklist so the gauge and the
 * list can never disagree. Keep the section list itself editable by the
 * team (a simple checkbox form posting to update_report_section.php) rather
 * than auto-detected — completion of a report section isn't something you
 * can infer from task/kanban activity.
 */
$deadline = new DateTime($viewData['reportDeadline'] ?? '2026-10-31');
$today = new DateTime('today');
$daysLeft = max(0, (int)$today->diff($deadline)->format('%r%a') * -1);
if ($today > $deadline) $daysLeft = 0;

$sections = $viewData['reportSections'] ?? [];
$total = count($sections) ?: 1;
$done = count(array_filter($sections, fn($s) => $s['done']));
$percent = (int)round(($done / $total) * 100);

$urgent = $daysLeft <= 14 && $percent < 80;
?>
<div class="card p-5" style="border-top: 2px solid <?php echo $urgent ? 'var(--danger)' : 'var(--accent-2)'; ?>;">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-sm font-head">
            <i class="fas fa-file-alt me-2" style="color: var(--accent-2);"></i>Draft report
        </h3>
        <div class="text-right">
            <div class="text-2xl font-bold font-mono-ui leading-none <?php echo $urgent ? 'text-danger' : ''; ?>">
                <?php echo $daysLeft; ?>
            </div>
            <div class="text-[10px] text-muted-ui uppercase tracking-wide">days left</div>
        </div>
    </div>

    <div class="flex items-center gap-5">
        <div class="relative h-24 w-24 flex-shrink-0">
            <canvas id="reportReadinessChart"></canvas>
            <div class="absolute inset-0 flex items-center justify-center flex-col" style="top: 8px;">
                <span class="font-bold text-lg font-mono-ui"><?php echo $percent; ?>%</span>
            </div>
        </div>
        <div class="flex-1 space-y-1.5">
            <?php foreach ($sections as $sec): ?>
                <div class="flex justify-between items-center text-xs">
                    <span class="<?php echo $sec['done'] ? '' : 'text-muted-ui'; ?>"><?php echo htmlspecialchars($sec['name']); ?></span>
                    <?php if ($sec['done']): ?>
                        <i class="fas fa-check-circle" style="color: var(--status-green);"></i>
                    <?php else: ?>
                        <i class="far fa-circle text-muted-ui"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($urgent): ?>
    <p class="text-xs text-danger mt-3 pt-3 border-t border-ui">
        <i class="fas fa-exclamation-triangle me-1"></i>Less than 2 weeks left and report is under 80% — flag this to your guide.
    </p>
    <?php endif; ?>
</div>

<script>
(function () {
    const canvas = document.getElementById('reportReadinessChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const rootStyles = getComputedStyle(document.documentElement);
    const accent2 = rootStyles.getPropertyValue('--accent-2').trim() || '#8b5cf6';
    const track = rootStyles.getPropertyValue('--border').trim() || '#2d2d2d';
    const percent = <?php echo $percent; ?>;

    new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [percent, 100 - percent],
                backgroundColor: [accent2, track],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '78%',
            rotation: -90,
            circumference: 360,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });
})();
</script>
