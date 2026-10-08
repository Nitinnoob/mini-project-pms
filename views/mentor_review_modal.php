<!-- Mentor Review & Instructions Modal -->
<div id="mentorReviewModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="card w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
        <button data-modal-close="#mentorReviewModal" onclick="document.getElementById('mentorReviewModal').classList.add('hidden')" class="absolute top-4 right-4 text-muted-ui hover:text-danger transition">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="font-head font-semibold text-2xl mb-1">Guide Feedback &amp; Action Items</h3>
        <p class="text-xs text-muted-ui mb-5">
            Week <span id="reviewModalWeekNumberDisplay" class="font-bold text-accent"></span> &middot; Mentor Review &amp; Directive Engine
        </p>

        <form method="POST" action="submit_mentor_review.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="project_id" value="<?php echo e($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
            <input type="hidden" name="week_number" id="reviewModalWeekNumberInput" value="">
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="review_status">
                    Review Decision
                </label>
                <select id="review_status" name="status" class="form-input text-sm" required>
                    <option value="approved">Approved — Meeting Satisfactory</option>
                    <option value="revision_needed">Revision Flagged — Resubmission Required</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="review_remarks">
                    <i class="fas fa-comment-dots text-muted-ui mr-1"></i> Guide Feedback &amp; Steering Remarks
                </label>
                <textarea id="review_remarks" name="mentor_remarks" rows="3" class="form-input text-sm" placeholder="Provide qualitative assessment, technical critique, and guidance..." required></textarea>
                <p class="text-[11px] text-muted-ui mt-1">Feedback is preserved in an append-only audit trail across reviews.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" for="review_instructions">
                    <i class="fas fa-tasks text-accent mr-1"></i> Action Items &amp; Instructions (One per line)
                </label>
                <textarea id="review_instructions" name="instructions_text" rows="3" class="form-input text-sm font-mono-ui" placeholder="e.g. Complete module 3 unit tests&#10;Fix SQL injection vulnerability in search endpoint&#10;Bring draft report chapter 2 to next Saturday meeting"></textarea>
                <p class="text-[11px] text-muted-ui mt-1">
                    Each non-empty line becomes a tracked instruction in <code class="font-mono text-accent">guide_instructions</code> that students must acknowledge.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5">
                    <i class="fas fa-user-check text-muted-ui mr-1"></i> Quick Attendance Sync
                </label>
                <div class="space-y-2 bg-overlay-subtle p-3 border border-ui rounded max-h-36 overflow-y-auto">
                    <?php foreach ($viewData['actualTeamRoster'] ?? [] as $member): ?>
                        <div class="flex items-center justify-between">
                            <label class="text-xs cursor-pointer select-none font-medium" for="att_<?php echo $member['id']; ?>">
                                <?php echo e($member['username']); ?>
                                <?php if ($member['is_leader']): ?>
                                    <span class="ml-1 text-[10px] font-mono-ui px-1 py-0.5 rounded bg-overlay-medium text-muted-ui">Leader</span>
                                <?php endif; ?>
                            </label>
                            <input type="checkbox" name="attendance[<?php echo $member['id']; ?>]" id="att_<?php echo $member['id']; ?>" value="1" class="w-4 h-4 cursor-pointer" style="accent-color: var(--accent-2);">
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-[11px] text-muted-ui mt-1">Tip: Use "Mark Attendance" for fine-grained present/absent/excused audit tracking.</p>
            </div>
            
            <div class="flex justify-end pt-3 mt-4 border-t border-ui gap-3">
                <button type="button" onclick="document.getElementById('mentorReviewModal').classList.add('hidden')" class="btn-ui px-4 py-2 text-xs font-semibold text-muted-ui hover:text-accent transition">Cancel</button>
                <button type="submit" class="btn-ui px-5 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-save"></i> Save Feedback &amp; Instructions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMentorReviewModal(weekNumber, submissionId, logDataStr) {
    document.getElementById('reviewModalWeekNumberDisplay').innerText = weekNumber;
    document.getElementById('reviewModalWeekNumberInput').value = weekNumber;
    
    // Reset form
    const form = document.querySelector('#mentorReviewModal form');
    form.reset();
    document.querySelectorAll('#mentorReviewModal input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('review_instructions').value = '';
    
    if (logDataStr && logDataStr !== 'null') {
        try {
            const logData = (typeof logDataStr === 'string') ? JSON.parse(logDataStr) : logDataStr;
            
            if (logData.status) {
                form.elements['status'].value = (logData.status === 'revision_needed' ? 'revision_needed' : 'approved');
            }
            if (logData.remarks) {
                form.elements['mentor_remarks'].value = logData.remarks;
            }
            if (logData.attendance) {
                for (const userId in logData.attendance) {
                    const cb = document.getElementById('att_' + userId);
                    if (cb) {
                        cb.checked = (logData.attendance[userId] == 1 || logData.attendance[userId] === 'present');
                    }
                }
            }
        } catch (e) {
            console.error("Failed to parse log data", e);
        }
    }
    
    document.getElementById('mentorReviewModal').classList.remove('hidden');
}
</script>
