<?php if (isset($viewData['myProjectId'])): ?>
<!-- Upload Deliverable Modal -->
<div id="uploadModal" class="modal-backdrop flex items-center justify-center hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;">
    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-md shadow-xl" style="background: var(--bg); border-color: var(--border);">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui" style="border-color: var(--border);">
            <h2 class="text-xl font-bold font-head">Upload Deliverable</h2>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-muted-ui hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        
        <form action="upload_deliverable.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($viewData['myProjectId']); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
            <input type="hidden" name="task_id" id="upload_task_id" value="">
            
            <div>
                <label class="block text-sm font-semibold mb-1">File</label>
                <input type="file" name="deliverable" required class="w-full bg-raised border border-ui rounded px-3 py-2 text-sm focus:outline-none focus:border-accent transition">
                <p class="text-xs text-muted-ui mt-1">Upload PDF, PPT, or ZIP code files.</p>
            </div>
            
            <div class="pt-4 border-t border-ui" style="border-color: var(--border);">
                <button type="submit" class="w-full btn-ui py-2 font-bold rounded" style="background: var(--accent); color: var(--bg);">
                    Upload File
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal(taskId) {
    document.getElementById('upload_task_id').value = taskId || '';
    document.getElementById('uploadModal').classList.remove('hidden');
}
</script>
<?php endif; ?>
