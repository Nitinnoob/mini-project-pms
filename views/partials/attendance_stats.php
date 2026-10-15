<?php
// Attendance stats bar for student view.
$att = $viewData['personalAttendance'] ?? ['total' => 0, 'present' => 0, 'percentage' => 0.0, 'has_shortage' => false];
$isTeacher = !empty($viewData['isTeacherDrilldown']);
if ($att['total'] > 0 || $isTeacher):
?>
<div class="card p-4 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <i class="fas fa-chart-pie text-accent"></i>
            <span class="text-sm font-semibold">Attendance</span>
        </div>
        <div class="flex items-center gap-3 text-xs font-mono-ui">
            <span class="text-muted-ui"><?php echo (int)$att['present']; ?> / <?php echo (int)$att['total']; ?> meetings</span>
            <span class="font-bold <?php echo $att['has_shortage'] ? 'text-status-red' : 'text-status-green'; ?>">
                <?php echo number_format((float)$att['percentage'], 1); ?>%
            </span>
        </div>
    </div>
    <?php if ($att['has_shortage']): ?>
    <span class="badge badge-danger text-xs font-bold flex items-center gap-1">
        <i class="fas fa-exclamation-triangle"></i> Shortage (&lt;75%)
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
