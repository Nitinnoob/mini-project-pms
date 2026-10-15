<?php
// CIE Marks summary card — ONLY visible in teacher drilldown, completely hidden from students.
if (!empty($viewData['isTeacherDrilldown']) && !empty($viewData['evaluationSheet'])):
    $sheet = $viewData['evaluationSheet'];
?>
<div class="card p-4">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <i class="fas fa-clipboard-check text-accent"></i>
            <span class="text-sm font-semibold">CIE Evaluation (Guide Only)</span>
        </div>
        <span class="text-xs font-mono-ui text-muted-ui">
            Report /50 &middot; Pres /25 &middot; Q&amp;A /25
        </span>
    </div>
    <div class="text-xs text-muted-ui">
        <?php
        $reportMarks = $sheet['report_marks'];
        echo 'Report: ' . ($reportMarks !== null ? number_format((float)$reportMarks, 1) . '/50' : 'Not entered');
        ?>
        &middot; <?php echo count($sheet['students'] ?? []); ?> student(s)
    </div>
</div>
<?php endif; ?>
