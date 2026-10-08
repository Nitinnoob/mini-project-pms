<?php if (isset($viewData['myProjectId'])): ?>
<!-- VTU Project Report Assistant: downloadable .docx template + compliance checklist -->
<div id="reportModal" class="modal-backdrop flex items-center justify-center hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;">
    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto" style="background: var(--bg); border-color: var(--border);">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui" style="border-color: var(--border);">
            <h2 class="text-xl font-bold font-head"><i class="fas fa-file-word me-2 text-accent"></i>VTU Report Assistant</h2>
            <button type="button" data-modal-close="#reportModal" onclick="document.getElementById('reportModal').classList.add('hidden')" class="text-muted-ui hover:text-accent transition" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <p class="text-sm text-muted-ui mb-4">
            Reports get sent back for formatting slips. Start from a pre-formatted VTU template, then tick off the checklist before you print.
        </p>

        <a href="download_vtu_template.php?classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>&project_id=<?php echo (int)$viewData['myProjectId']; ?>"
           class="btn-ui flex items-center justify-center gap-2 w-full py-2.5 text-sm font-semibold mb-2 shadow" style="background: var(--accent); color: var(--bg);">
            <i class="fas fa-file-word"></i> Download Pre-filled VTU Report (.docx)
        </a>
        <p class="text-xs text-muted-ui mb-5">
            Pre-filled with project metadata, team members &amp; USNs, faculty guide, HOD, <strong>Saturday weekly guide meeting logs</strong>, action directives, attendance summary, and <strong>Continuous Internal Evaluation (CIE) marks</strong>. Includes cover page, certificate, declaration, abstract, TOC, and chapters 1&ndash;6. Open in Word and press <span class="font-mono-ui font-bold">F9</span> to refresh table of contents.
        </p>

        <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold text-sm font-head">Formatting checklist</h3>
            <span id="vtuChecklistCount" class="badge badge-muted text-xs">0 / 0</span>
        </div>
        <ul id="vtuChecklist" class="space-y-2 text-sm" data-storage-key="pms-vtu-checklist-<?php echo (int)$viewData['myProjectId']; ?>">
            <?php
            $vtuChecks = [
                'Body text is Times New Roman, 12 pt',
                'Line spacing is 1.5 throughout the body',
                'Page margins are 1 inch, with a 1.25 inch left margin for binding',
                'Chapter headings 14 pt bold capitals; section headings bold; consistent numbering (1.1, 1.1.1)',
                'Cover page, certificate and declaration carry every team member\'s name and USN',
                'Certificate has signature spaces for Guide, HOD, Principal and External Examiner',
                'Preliminary pages use roman numerals (i, ii, iii); chapters start at page 1',
                'Figures and tables are captioned and numbered by chapter (Fig. 3.1, Table 4.2)',
                'Contents, list of figures and list of tables are updated and match page numbers',
                'References follow IEEE format and every entry is cited in the text as [1], [2]',
                'Report checked for plagiarism and spelling; sources acknowledged',
                'Confirmed any college or department specific changes with your guide',
            ];
            foreach ($vtuChecks as $i => $label): ?>
            <li>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" class="mt-1 vtu-check" data-idx="<?php echo $i; ?>">
                    <span><?php echo e($label); ?></span>
                </label>
            </li>
            <?php endforeach; ?>
        </ul>
        <p class="text-[11px] text-muted-ui mt-4">The template follows commonly used VTU report guidelines. Always cross-check the latest circular from your department.</p>
    </div>
</div>
<script>
(function () {
    var list = document.getElementById('vtuChecklist');
    if (!list) return;
    var key = list.getAttribute('data-storage-key');
    var boxes = list.querySelectorAll('.vtu-check');
    var saved = [];
    try { saved = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
    function refresh() {
        var n = 0, state = [];
        boxes.forEach(function (b) { if (b.checked) { n++; } state.push(b.checked ? 1 : 0); });
        document.getElementById('vtuChecklistCount').textContent = n + ' / ' + boxes.length;
        try { localStorage.setItem(key, JSON.stringify(state)); } catch (e) {}
    }
    boxes.forEach(function (b, i) {
        b.checked = !!saved[i];
        b.addEventListener('change', refresh);
    });
    refresh();
})();
</script>
<?php endif; ?>
