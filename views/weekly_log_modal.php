<!-- Weekly Log Modal -->
<div id="weeklyLogModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="card w-full max-w-lg p-6 relative">
        <button onclick="document.getElementById('weeklyLogModal').classList.add('hidden')" class="absolute top-4 right-4 text-muted-ui hover:text-danger transition">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h3 class="font-head font-semibold text-2xl mb-2">Submit Weekly Log</h3>
        <p class="text-sm text-muted-ui mb-6">Week <span id="modalWeekNumberDisplay" class="font-bold"></span></p>

        <form method="POST" action="submit_weekly_log.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
            <input type="hidden" name="week_number" id="modalWeekNumberInput" value="">
            
            <div>
                <label class="block text-sm font-semibold mb-2">Work Summary</label>
                <textarea name="work_summary" rows="3" class="form-input" placeholder="What did the team accomplish this week?" required></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Next Steps</label>
                <textarea name="next_steps" rows="2" class="form-input" placeholder="What are the goals for next week?" required></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Attach Files (Code, Reports, Screenshots)</label>
                <input type="file" name="weekly_files[]" multiple class="form-input text-sm p-2 cursor-pointer" accept=".zip,.pdf,.docx,.txt,.jpg,.png">
                <p class="text-xs text-muted-ui mt-1">Hold Ctrl/Cmd to select multiple files.</p>
            </div>
            
            <div class="flex justify-end pt-4 mt-6 border-t border-ui gap-3">
                <button type="button" onclick="document.getElementById('weeklyLogModal').classList.add('hidden')" class="px-5 py-2 text-sm font-semibold text-muted-ui hover:text-accent transition">Cancel</button>
                <button type="submit" class="btn-ui px-5 py-2 text-sm font-semibold" style="background: var(--accent-2); color: var(--bg);">Submit Log</button>
            </div>
        </form>
    </div>
</div>

<script>
function openWeeklyLogModal(weekNumber) {
    document.getElementById('modalWeekNumberDisplay').innerText = weekNumber;
    document.getElementById('modalWeekNumberInput').value = weekNumber;
    document.getElementById('weeklyLogModal').classList.remove('hidden');
}
</script>
