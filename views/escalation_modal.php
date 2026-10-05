<?php if (isset($viewData['myProjectId']) && empty($viewData['isTeacherDrilldown'])): ?>
<!-- Escalation Flare Modal: raises a blocker into the `issues` table -->
<div id="escalationModal" class="modal-backdrop flex items-center justify-center hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;">
    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-md shadow-xl max-h-[90vh] overflow-y-auto" style="background: var(--bg); border-color: var(--border);">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui" style="border-color: var(--border);">
            <h2 class="text-xl font-bold font-head"><i class="fas fa-triangle-exclamation me-2 text-danger"></i>Raise a blocker</h2>
            <button type="button" onclick="document.getElementById('escalationModal').classList.add('hidden')" class="text-muted-ui hover:text-accent transition" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-xs text-muted-ui mb-4">Stuck on something? Your mentor and project leader will be alerted straight away.</p>

        <form action="raise_issue.php" method="POST" class="space-y-4">
            <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">

            <div>
                <label class="block text-sm font-semibold mb-1">Blocker title</label>
                <input type="text" name="title" required maxlength="200" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition" placeholder="e.g. Database server not reachable from lab">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">What is happening?</label>
                <textarea name="description" required rows="4" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition" placeholder="Describe the problem, what you tried, and what help you need."></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Severity</label>
                <select name="severity" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                    <option value="low">Low - can work around it</option>
                    <option value="medium" selected>Medium - slowing the team down</option>
                    <option value="high">High - a milestone is at risk</option>
                    <option value="critical">Critical - work has stopped</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Impacted phase</label>
                    <select name="week_number" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                        <option value="">Not phase-specific</option>
                        <?php foreach (($viewData['phases'] ?? []) as $p): ?>
                            <option value="<?php echo (int)$p['week_number']; ?>" <?php echo !empty($p['is_current']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Impacted task</label>
                    <select name="task_id" class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                        <option value="">No specific task</option>
                        <?php foreach (($viewData['tasks'] ?? []) as $statusGroup): foreach ($statusGroup as $t): ?>
                            <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars(mb_strimwidth($t['title'], 0, 40, '...')); ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pt-4 border-t border-ui" style="border-color: var(--border);">
                <button type="submit" class="w-full btn-ui py-2 font-bold rounded" style="background: var(--danger); color: #fff;">
                    Send escalation
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
