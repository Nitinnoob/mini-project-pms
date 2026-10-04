<?php
/**
 * Per-member guide-meeting attendance, for leader.php's Team tab or
 * teacher.php's drilldown. Individual bars, not a shared-pie doughnut —
 * attendance marks are deducted per student, not split across the team.
 *
 * Expects $viewData['actualTeamRoster'] entries to include 'attendancePercent'.
 */
$roster = $viewData['actualTeamRoster'] ?? [];
?>
<div class="card p-5">
    <h3 class="font-semibold text-sm mb-4 font-head">
        <i class="fas fa-users me-2" style="color: var(--accent);"></i>Team attendance
    </h3>
    <div class="relative" style="height: <?php echo max(120, count($roster) * 36); ?>px;">
        <canvas id="memberAttendanceChart"></canvas>
    </div>
</div>

<script>
(function () {
    const canvas = document.getElementById('memberAttendanceChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const rootStyles = getComputedStyle(document.documentElement);
    const accent = rootStyles.getPropertyValue('--accent').trim() || '#4f46e5';
    const statusRed = rootStyles.getPropertyValue('--status-red').trim() || '#b42318';
    const muted = rootStyles.getPropertyValue('--muted').trim() || '#9ca3af';

    const roster = <?php echo json_encode(array_map(fn($m) => [
        'name' => $m['username'] ?? $m['name'] ?? 'Member',
        'percent' => (int)($m['attendancePercent'] ?? 0),
    ], $roster)); ?>;

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: roster.map(m => m.name),
            datasets: [{
                data: roster.map(m => m.percent),
                backgroundColor: roster.map(m => m.percent < 80 ? statusRed : accent),
                borderRadius: 4,
                barThickness: 18,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { min: 0, max: 100, ticks: { color: muted, callback: v => v + '%' }, grid: { color: 'rgba(128,128,128,0.1)' } },
                y: { ticks: { color: muted }, grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();
</script>
