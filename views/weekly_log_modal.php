<!-- Weekly Log Modal -->
<div id="weeklyLogModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="card w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
        <button data-modal-close="#weeklyLogModal" onclick="document.getElementById('weeklyLogModal').classList.add('hidden')" class="absolute top-4 right-4 text-muted-ui hover:text-danger transition">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="font-head font-semibold text-2xl mb-1">Weekly Meeting Update</h3>
        <p class="text-xs text-muted-ui mb-5">
            Week <span id="modalWeekNumberDisplay" class="font-bold text-accent"></span> &middot; Any active team member can submit
        </p>

        <form method="POST" action="submit_weekly_log.php" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="project_id" value="<?php echo e($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
            <input type="hidden" name="week_number" id="modalWeekNumberInput" value="">
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="log_work_done">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Work Done this Week
                </label>
                <textarea id="log_work_done" name="work_done" rows="3" class="form-input text-sm" placeholder="What key tasks, experiments, or modules were completed this week?" required></textarea>
            </div>
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="log_next_steps">
                    <i class="fas fa-arrow-circle-right text-accent mr-1"></i> Next Steps for Next Saturday
                </label>
                <textarea id="log_next_steps" name="next_steps" rows="2" class="form-input text-sm" placeholder="What targets and milestones are planned for the upcoming week?" required></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="log_blockers">
                    <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Blockers &amp; Impediments
                </label>
                <textarea id="log_blockers" name="blockers" rows="2" class="form-input text-sm" placeholder="Any technical hurdles, missing hardware/lab access, or guide guidance needed? (Leave empty if none)"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5">
                    <i class="fas fa-paperclip text-muted-ui mr-1"></i> Attach Artifacts &amp; Documents
                </label>
                <input type="file" name="weekly_files[]" multiple class="form-input text-xs p-2 cursor-pointer" accept=".pdf,.docx,.doc,.pptx,.ppt,.zip,.txt,.md,.csv,.png,.jpg,.jpeg">
                <p class="text-[11px] text-muted-ui mt-1">
                    Allowed: PDF, Word, PowerPoint, ZIP, Images, Text (Max 10MB per file). Multi-select supported.
                </p>
            </div>
            
            <div class="flex justify-end pt-3 mt-4 border-t border-ui gap-3">
                <button type="button" onclick="document.getElementById('weeklyLogModal').classList.add('hidden')" class="btn-ui px-4 py-2 text-xs font-semibold text-muted-ui hover:text-accent transition">Cancel</button>
                <button type="submit" class="btn-ui px-5 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-paper-plane"></i> Submit Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openWeeklyLogModal(weekNumber, prefillJsonStr) {
    const modal = document.getElementById('weeklyLogModal');
    const form = modal.querySelector('form');
    document.getElementById('modalWeekNumberDisplay').innerText = weekNumber;
    document.getElementById('modalWeekNumberInput').value = weekNumber;

    // Reset default inputs
    document.getElementById('log_work_done').value = '';
    document.getElementById('log_next_steps').value = '';
    document.getElementById('log_blockers').value = '';

    if (prefillJsonStr) {
        try {
            const data = (typeof prefillJsonStr === 'string') ? JSON.parse(prefillJsonStr) : prefillJsonStr;
            if (data.work_done || data.work_summary) {
                document.getElementById('log_work_done').value = data.work_done || data.work_summary || '';
            }
            if (data.next_steps) {
                document.getElementById('log_next_steps').value = data.next_steps;
            }
            if (data.blockers) {
                document.getElementById('log_blockers').value = data.blockers;
            }
        } catch (e) {
            console.warn('Could not parse prefill data for weekly log modal', e);
        }
    }

    modal.classList.remove('hidden');
}
</script>
