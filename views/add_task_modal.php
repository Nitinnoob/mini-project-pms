<?php if (isset($viewData['myProjectId'])): ?>
<!-- Add Task Modal -->
<div id="addTaskModal" class="modal-backdrop flex items-center justify-center hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;">
    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-md shadow-xl" style="background: var(--bg); border-color: var(--border);">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui" style="border-color: var(--border);">
            <h2 class="text-xl font-bold font-head">Add New Task</h2>
            <button onclick="document.getElementById('addTaskModal').classList.add('hidden')" class="text-muted-ui hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        
        <form action="add_task.php" method="POST" class="space-y-4">
            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
            
            <div>
                <label class="block text-sm font-semibold mb-1">Task Title</label>
                <input type="text" name="title" required class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition" placeholder="e.g. Write Phase 1 Report">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1">Assign To (Optional)</label>
                <select name="assigned_to" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                    <option value="">Unassigned</option>
                    <?php if (!empty($viewData['actualTeamRoster'])): ?>
                        <?php foreach($viewData['actualTeamRoster'] as $member): ?>
                            <option value="<?php echo htmlspecialchars($member['id']); ?>">
                                <?php echo htmlspecialchars($member['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1">VTU Phase (Milestone)</label>
                <select name="milestone" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                    <option value="Synopsis">Synopsis / Idea Pitch</option>
                    <option value="Phase 1">Phase 1 (Design & Architecture)</option>
                    <option value="Phase 2">Phase 2 (Implementation)</option>
                    <option value="Final Demo">Final Demo & Report</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Priority</label>
                <select name="priority" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                </select>
            </div>
            
            <div class="pt-4 border-t border-ui" style="border-color: var(--border);">
                <button type="submit" class="w-full btn-ui py-2 font-bold rounded" style="background: var(--accent); color: var(--bg);">
                    Create Task
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
