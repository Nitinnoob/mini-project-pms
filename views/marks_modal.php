<!-- ============================================================ -->
<!-- EVALUATION MARKS MODAL (Simple Entry Desk)                  -->
<!-- ============================================================ -->
<?php
$evalSheet = $viewData['evaluationSheet'] ?? null;
$canEvaluate = !empty($viewData['canEvaluateProject']);
?>

<div id="marksModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
    <div class="card w-full max-w-3xl max-h-[90vh] flex flex-col p-6 border-ui bg-panel shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="flex justify-between items-start border-b border-ui pb-4 mb-4">
            <div>
                <h3 class="text-lg font-head font-bold flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-accent"></i> Evaluation Marks
                </h3>
                <p class="text-xs text-muted-ui mt-1">50 Report / 25 Presentation / 25 Q&amp;A (100 Total)</p>
            </div>
            <button type="button" onclick="document.getElementById('marksModal').classList.add('hidden')" class="text-muted-ui hover:text-text p-1 transition">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <?php if ($evalSheet && $canEvaluate): ?>
        <form method="POST" action="save_marks.php" id="marksForm" class="flex-1 flex flex-col overflow-hidden">
            <input type="hidden" name="project_id" value="<?php echo (int)$evalSheet['project_id']; ?>">
            <input type="hidden" name="classroom_id" value="<?php echo (int)($viewData['classroom_id'] ?? 0); ?>">

            <div class="flex-1 overflow-y-auto pr-1 space-y-5">

                <!-- Shared Report Marks -->
                <div class="p-4 rounded border border-ui bg-raised">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-sm flex items-center gap-2">
                                <i class="fas fa-file-alt text-accent"></i> Project Report (Shared)
                            </div>
                            <p class="text-[11px] text-muted-ui mt-0.5">Score shared by all team members.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-mono-ui font-semibold text-muted-ui">Score (Max 50):</label>
                            <input type="number" step="0.5" min="0" max="50" name="report_marks" id="input_report_marks"
                                   value="<?php echo $evalSheet['report_marks'] !== null ? (float)$evalSheet['report_marks'] : ''; ?>"
                                   placeholder="0 - 50" oninput="recalcAll()"
                                   class="form-input text-sm font-mono-ui font-bold text-center w-24 py-1.5 px-2 bg-panel">
                        </div>
                    </div>
                </div>

                <!-- Individual Student Marks -->
                <div class="border border-ui rounded overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse min-w-[450px]">
                            <thead>
                                <tr class="bg-overlay-subtle border-b border-ui text-muted-ui font-mono-ui">
                                    <th class="p-2.5">Student / USN</th>
                                    <th class="p-2.5 text-center" style="width: 105px;">Presentation<br><span class="font-normal text-[10px]">(/25)</span></th>
                                    <th class="p-2.5 text-center" style="width: 105px;">Viva Q&amp;A<br><span class="font-normal text-[10px]">(/25)</span></th>
                                    <th class="p-2.5 text-right" style="width: 90px;">Total<br><span class="font-normal text-[10px]">(/100)</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ui">
                                <?php foreach ($evalSheet['students'] as $stu): ?>
                                <?php
                                    $uid = (int)$stu['user_id'];
                                    $pMarks = $stu['presentation_marks'] !== null ? (float)$stu['presentation_marks'] : '';
                                    $qMarks = $stu['qa_marks'] !== null ? (float)$stu['qa_marks'] : '';
                                ?>
                                <tr class="hover-overlay-subtle">
                                    <td class="p-2.5">
                                        <div class="font-semibold text-text"><?php echo e($stu['username']); ?></div>
                                        <div class="text-[11px] text-muted-ui font-mono-ui"><?php echo e($stu['usn'] ?? ''); ?></div>
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <input type="number" step="0.5" min="0" max="25"
                                               name="students[<?php echo $uid; ?>][presentation]"
                                               id="pres_<?php echo $uid; ?>" value="<?php echo $pMarks; ?>"
                                               placeholder="0-25" oninput="recalcRow(<?php echo $uid; ?>)"
                                               class="form-input text-xs font-mono-ui text-center w-20 py-1 px-1.5 bg-panel">
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <input type="number" step="0.5" min="0" max="25"
                                               name="students[<?php echo $uid; ?>][qa]"
                                               id="qa_<?php echo $uid; ?>" value="<?php echo $qMarks; ?>"
                                               placeholder="0-25" oninput="recalcRow(<?php echo $uid; ?>)"
                                               class="form-input text-xs font-mono-ui text-center w-20 py-1 px-1.5 bg-panel">
                                    </td>
                                    <td class="p-2.5 text-right font-mono-ui font-bold text-sm">
                                        <span id="total_<?php echo $uid; ?>" class="text-accent">
                                            <?php echo $stu['total_marks'] !== null ? number_format((float)$stu['total_marks'], 1) : '-'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-ui pt-4 mt-4 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <a href="export_marks.php?classroom_id=<?php echo (int)($viewData['classroom_id'] ?? 0); ?>&project_id=<?php echo (int)$evalSheet['project_id']; ?>&format=print" target="_blank" class="btn-ui px-3 py-1.5 text-xs text-muted-ui hover:text-text flex items-center gap-1.5">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="export_marks.php?classroom_id=<?php echo (int)($viewData['classroom_id'] ?? 0); ?>&project_id=<?php echo (int)$evalSheet['project_id']; ?>&format=csv" class="btn-ui px-3 py-1.5 text-xs text-muted-ui hover:text-text flex items-center gap-1.5">
                        <i class="fas fa-download"></i> CSV
                    </a>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="document.getElementById('marksModal').classList.add('hidden')" class="btn-ui px-4 py-1.5 text-xs text-muted-ui">Cancel</button>
                    <button type="submit" class="btn-ui px-5 py-1.5 text-xs font-semibold" style="background: var(--accent); color: var(--bg);">
                        <i class="fas fa-save mr-1"></i> Save Marks
                    </button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="py-12 text-center text-muted-ui text-sm">No evaluation data available.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function recalcRow(uid) {
    var r = document.getElementById('input_report_marks');
    var p = document.getElementById('pres_' + uid);
    var q = document.getElementById('qa_' + uid);
    var t = document.getElementById('total_' + uid);
    if (!t) return;
    var rv = r && r.value !== '' ? parseFloat(r.value) : 0;
    var pv = p && p.value !== '' ? parseFloat(p.value) : 0;
    var qv = q && q.value !== '' ? parseFloat(q.value) : 0;
    var any = (r && r.value !== '') || (p && p.value !== '') || (q && q.value !== '');
    t.textContent = any ? (rv + pv + qv).toFixed(1) : '-';
}
function recalcAll() {
    <?php if (!empty($evalSheet['students'])): ?>
    <?php foreach ($evalSheet['students'] as $s): ?>
    recalcRow(<?php echo (int)$s['user_id']; ?>);
    <?php endforeach; ?>
    <?php endif; ?>
}
</script>
