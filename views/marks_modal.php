<!-- ============================================================ -->
<!-- EVALUATION MARKS MODAL (Fast Entry Desk)                   -->
<!-- ============================================================ -->
<?php
$evalSheet = $viewData['evaluationSheet'] ?? null;
$evalConfig = $viewData['evaluationConfig'] ?? marks_get_config();
$isFinalized = !empty($evalSheet['is_finalized']);
$canEvaluate = !empty($viewData['canEvaluateProject']);
$canAdminOverride = !empty($viewData['isCoordinator']) || (!empty($viewData['classroomRole']) && $viewData['classroomRole'] === 'Admin');
$isLockedForUser = $isFinalized && !$canAdminOverride;
?>

<div id="marksModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
    <div class="card w-full max-w-3xl max-h-[90vh] flex flex-col p-6 border-ui bg-panel shadow-2xl overflow-hidden animate-fadeIn">
        
        <!-- Header -->
        <div class="flex justify-between items-start border-b border-ui pb-4 mb-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h3 class="text-lg font-head font-bold flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-accent"></i> Evaluation Marks Sheet
                    </h3>
                    <?php if ($isFinalized): ?>
                        <span class="badge badge-green font-mono-ui text-[11px] flex items-center gap-1">
                            <i class="fas fa-lock"></i> Finalized
                        </span>
                    <?php else: ?>
                        <span class="badge badge-muted font-mono-ui text-[11px]">
                            Draft
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-muted-ui mt-1">
                    Continuous Internal Evaluation (CIE) &middot; 50 Report / 25 Pres. / 25 Q&A (100 Total)
                </p>
            </div>
            <button type="button" onclick="document.getElementById('marksModal').classList.add('hidden')" class="text-muted-ui hover:text-white p-1 transition">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <?php if ($evalSheet): ?>
        <form method="POST" action="save_marks.php" id="marksEvaluationForm" class="flex-1 flex flex-col overflow-hidden">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="project_id" value="<?php echo (int)$evalSheet['project']['id']; ?>">
            <input type="hidden" name="classroom_id" value="<?php echo (int)($viewData['classroom_id'] ?? $evalSheet['project']['classroom_id']); ?>">
            <input type="hidden" name="action" id="marksFormAction" value="save">

            <div class="flex-1 overflow-y-auto pr-1 space-y-5">
                
                <!-- Finalization Notice / Admin Reason Notice -->
                <?php if ($isFinalized): ?>
                    <div class="p-3.5 rounded border border-emerald-500/30 bg-emerald-950/20 text-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-400"></i>
                                <div>
                                    <strong class="text-emerald-300">Marks Finalized:</strong> 
                                    Locked on <?php echo e($evalSheet['project_marks']['finalized_at'] ?? 'N/A'); ?>
                                    <?php if (!empty($evalSheet['project_marks']['finalized_by_name'])): ?>
                                        by <?php echo e($evalSheet['project_marks']['finalized_by_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($canAdminOverride): ?>
                                <button type="button" onclick="submitMarksAction('unlock')" class="btn-ui px-2.5 py-1 text-[11px] text-amber-300 border-amber-500/40 hover:border-amber-400">
                                    <i class="fas fa-unlock mr-1"></i> Unlock Marks
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($canAdminOverride): ?>
                            <div class="mt-3 pt-3 border-t border-emerald-500/20">
                                <label class="block font-semibold text-[11px] text-amber-300 mb-1">
                                    Admin Modification Reason (Mandatory for post-finalization edits):
                                </label>
                                <input type="text" name="reason" id="adminModReason" placeholder="State reason for adjusting finalized marks..."
                                       class="form-input text-xs w-full bg-panel">
                            </div>
                        <?php else: ?>
                            <p class="text-muted-ui mt-2 text-[11px]">
                                Finalized marks are read-only. Please contact the classroom coordinator or Admin if an adjustment is required.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Shared Project Report Marks Card -->
                <div class="p-4 rounded border border-ui bg-raised">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-sm flex items-center gap-2">
                                <i class="fas fa-file-alt text-accent"></i> Project Report Marks (Shared)
                            </div>
                            <p class="text-[11px] text-muted-ui mt-0.5">
                                Combined documentation, literature survey, and methodology score shared by all team members.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-mono-ui font-semibold text-muted-ui">Score (Max 50):</label>
                            <input type="number" step="0.5" min="0" max="50" name="report_marks" id="input_report_marks"
                                   value="<?php echo $evalSheet['project_marks']['report_marks'] !== null ? (float)$evalSheet['project_marks']['report_marks'] : ''; ?>"
                                   placeholder="0 - 50"
                                   oninput="recalculateAllTotals()"
                                   <?php echo $isLockedForUser ? 'disabled' : ''; ?>
                                   class="form-input text-sm font-mono-ui font-bold text-center w-24 py-1.5 px-2 bg-panel">
                        </div>
                    </div>
                </div>

                <!-- Individual Student Evaluation Table -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-head font-semibold text-xs uppercase tracking-wider text-muted-ui">
                            Individual Student Continuous Evaluation (Viva & Demo)
                        </h4>
                        <span class="text-[11px] text-muted-ui font-mono-ui">
                            Pres. (Max 25) + Q&A (Max 25)
                        </span>
                    </div>

                    <div class="border border-ui rounded overflow-hidden">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-overlay-subtle border-b border-ui text-muted-ui font-mono-ui">
                                    <th class="p-2.5">Student / USN</th>
                                    <th class="p-2.5 text-center">Attendance</th>
                                    <th class="p-2.5 text-center" style="width: 105px;">Presentation<br><span class="font-normal text-[10px]">(/25)</span></th>
                                    <th class="p-2.5 text-center" style="width: 105px;">Viva Q&A<br><span class="font-normal text-[10px]">(/25)</span></th>
                                    <th class="p-2.5 text-right" style="width: 90px;">Total<br><span class="font-normal text-[10px]">(/100)</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ui">
                                <?php foreach ($evalSheet['students'] as $stu): ?>
                                    <?php
                                    $uid = (int)$stu['user_id'];
                                    $att = $stu['attendance'];
                                    $pMarks = $stu['presentation_marks'] !== null ? (float)$stu['presentation_marks'] : '';
                                    $qMarks = $stu['qa_marks'] !== null ? (float)$stu['qa_marks'] : '';
                                    ?>
                                    <tr class="hover-overlay-subtle">
                                        <td class="p-2.5">
                                            <div class="font-semibold text-white flex items-center gap-1.5">
                                                <?php echo e($stu['username']); ?>
                                                <?php if ($stu['is_leader']): ?>
                                                    <span class="badge text-[9px] px-1 py-0.5" style="background: var(--accent); color: var(--bg);">Leader</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[11px] text-muted-ui font-mono-ui">
                                                <?php echo e($stu['usn']); ?>
                                            </div>
                                        </td>
                                        <td class="p-2.5 text-center">
                                            <span class="font-mono-ui font-semibold <?php echo $stu['is_shortage'] ? 'text-rose-400' : 'text-emerald-400'; ?>">
                                                <?php echo $stu['attendance_pct']; ?>%
                                            </span>
                                            <?php if ($stu['is_shortage']): ?>
                                                <span class="block text-[9px] text-rose-400 font-bold uppercase tracking-wider">Shortage</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-2.5 text-center">
                                            <input type="number" step="0.5" min="0" max="25"
                                                   name="students[<?php echo $uid; ?>][presentation_marks]"
                                                   id="pres_<?php echo $uid; ?>"
                                                   value="<?php echo $pMarks; ?>"
                                                   placeholder="0-25"
                                                   oninput="recalculateRowTotal(<?php echo $uid; ?>)"
                                                   <?php echo $isLockedForUser ? 'disabled' : ''; ?>
                                                   class="form-input text-xs font-mono-ui text-center w-20 py-1 px-1.5 bg-panel">
                                        </td>
                                        <td class="p-2.5 text-center">
                                            <input type="number" step="0.5" min="0" max="25"
                                                   name="students[<?php echo $uid; ?>][qa_marks]"
                                                   id="qa_<?php echo $uid; ?>"
                                                   value="<?php echo $qMarks; ?>"
                                                   placeholder="0-25"
                                                   oninput="recalculateRowTotal(<?php echo $uid; ?>)"
                                                   <?php echo $isLockedForUser ? 'disabled' : ''; ?>
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

            <!-- Modal Footer -->
            <div class="border-t border-ui pt-4 mt-4 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$evalSheet['project']['classroom_id']); ?>&project_id=<?php echo (int)$evalSheet['project']['id']; ?>&format=print" target="_blank" class="btn-ui px-3 py-1.5 text-xs text-muted-ui hover:text-white flex items-center gap-1.5">
                        <i class="fas fa-print"></i> Print Sheet
                    </a>
                    <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$evalSheet['project']['classroom_id']); ?>&project_id=<?php echo (int)$evalSheet['project']['id']; ?>&format=csv" class="btn-ui px-3 py-1.5 text-xs text-muted-ui hover:text-white flex items-center gap-1.5">
                        <i class="fas fa-download"></i> CSV
                    </a>
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="document.getElementById('marksModal').classList.add('hidden')" class="btn-ui px-4 py-1.5 text-xs text-muted-ui">
                        Cancel
                    </button>

                    <?php if (!$isLockedForUser && $canEvaluate): ?>
                        <button type="button" onclick="submitMarksAction('save')" class="btn-ui px-4 py-1.5 text-xs font-semibold" style="border-color: var(--accent); color: var(--accent);">
                            <i class="fas fa-save mr-1"></i> Save Draft
                        </button>
                        <button type="button" onclick="submitMarksAction('finalize')" class="btn-ui px-4 py-1.5 text-xs font-semibold" style="background: var(--accent); color: var(--bg);">
                            <i class="fas fa-lock mr-1"></i> Finalize Evaluation
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </form>
        <?php else: ?>
            <div class="py-12 text-center text-muted-ui text-sm">
                <p>No project selected for marks evaluation.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function recalculateRowTotal(uid) {
    const reportInput = document.getElementById('input_report_marks');
    const presInput = document.getElementById('pres_' + uid);
    const qaInput = document.getElementById('qa_' + uid);
    const totalEl = document.getElementById('total_' + uid);

    if (!totalEl) return;

    const reportVal = reportInput && reportInput.value !== '' ? parseFloat(reportInput.value) : 0;
    const presVal = presInput && presInput.value !== '' ? parseFloat(presInput.value) : 0;
    const qaVal = qaInput && qaInput.value !== '' ? parseFloat(qaInput.value) : 0;

    const hasAny = (reportInput && reportInput.value !== '') || (presInput && presInput.value !== '') || (qaInput && qaInput.value !== '');

    if (!hasAny) {
        totalEl.textContent = '-';
    } else {
        const sum = (reportVal + presVal + qaVal).toFixed(1);
        totalEl.textContent = sum;
    }
}

function recalculateAllTotals() {
    <?php if (!empty($evalSheet['students'])): ?>
        <?php foreach ($evalSheet['students'] as $s): ?>
            recalculateRowTotal(<?php echo (int)$s['user_id']; ?>);
        <?php endforeach; ?>
    <?php endif; ?>
}

function submitMarksAction(action) {
    const form = document.getElementById('marksEvaluationForm');
    const actionInput = document.getElementById('marksFormAction');
    const reasonInput = document.getElementById('adminModReason');

    if (!form || !actionInput) return;

    actionInput.value = action;

    if (action === 'finalize') {
        if (!confirm('Are you sure you want to FINALIZE evaluation marks for this project? Once finalized, edits are locked.')) {
            return;
        }
    } else if (action === 'unlock') {
        if (reasonInput && reasonInput.value.trim() === '') {
            alert('Please enter a mandatory reason for unlocking marks.');
            reasonInput.focus();
            return;
        }
        if (!confirm('Unlock evaluation marks for this project? This change will be logged.')) {
            return;
        }
    } else if (action === 'save') {
        <?php if ($isFinalized && $canAdminOverride): ?>
            if (reasonInput && reasonInput.value.trim() === '') {
                alert('Please enter a mandatory reason for updating finalized marks.');
                reasonInput.focus();
                return;
            }
        <?php endif; ?>
    }

    form.submit();
}
</script>
