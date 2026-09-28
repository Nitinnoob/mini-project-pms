<!-- Mentor Review Modal -->
<div id="mentorReviewModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="card w-full max-w-lg p-6 relative">
        <button onclick="document.getElementById('mentorReviewModal').classList.add('hidden')" class="absolute top-4 right-4 text-muted-ui hover:text-danger transition">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="font-head font-semibold text-2xl mb-2">Mentor Review</h3>
        <p class="text-sm text-muted-ui mb-6">Week <span id="reviewModalWeekNumberDisplay" class="font-bold"></span></p>

        <form method="POST" action="submit_mentor_review.php" class="space-y-4">
            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
            <input type="hidden" name="week_number" id="reviewModalWeekNumberInput" value="">
            
            <div>
                <label class="block text-sm font-semibold mb-2">Review Status</label>
                <select name="status" class="form-input" required>
                    <option value="approved">Approved</option>
                    <option value="revision_needed">Revision Flagged</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Remarks & Suggestions</label>
                <textarea name="mentor_remarks" rows="3" class="form-input" placeholder="Any feedback for the team?" required></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Meeting Attendance</label>
                <p class="text-xs text-muted-ui mb-3">Check the box if the student was present.</p>
                <div class="space-y-2 bg-overlay-subtle p-3 border border-ui rounded">
                    <?php foreach ($viewData['actualTeamRoster'] ?? [] as $member): ?>
                        <div class="flex items-center justify-between">
                            <label class="text-sm cursor-pointer select-none" for="att_<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['username']); ?>
                                <?php if ($member['is_leader']): ?>
                                    <span class="ml-2 text-xs font-mono-ui px-1 py-0.5 rounded bg-overlay-medium text-muted-ui">Leader</span>
                                <?php endif; ?>
                            </label>
                            <input type="checkbox" name="attendance[<?php echo $member['id']; ?>]" id="att_<?php echo $member['id']; ?>" value="1" class="w-4 h-4 cursor-pointer" style="accent-color: var(--accent-2);">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex justify-end pt-4 mt-6 border-t border-ui gap-3">
                <button type="button" onclick="document.getElementById('mentorReviewModal').classList.add('hidden')" class="px-5 py-2 text-sm font-semibold text-muted-ui hover:text-accent transition">Cancel</button>
                <button type="submit" class="btn-ui px-5 py-2 text-sm font-semibold" style="background: var(--accent-2); color: var(--bg);">Save Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMentorReviewModal(weekNumber, submissionId, logDataStr) {
    document.getElementById('reviewModalWeekNumberDisplay').innerText = weekNumber;
    document.getElementById('reviewModalWeekNumberInput').value = weekNumber;
    
    // Reset the form
    const form = document.querySelector('#mentorReviewModal form');
    form.reset();
    
    // Uncheck all attendance checkboxes
    document.querySelectorAll('#mentorReviewModal input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    if (logDataStr && logDataStr !== 'null') {
        try {
            const logData = JSON.parse(logDataStr);
            
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
                        cb.checked = (logData.attendance[userId] == 1);
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
