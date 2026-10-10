<!-- views/marks_modal.php - CIE marks entry (Report 50 + Presentation 25 + Viva 25) with project evidence alongside -->
<div id="marksModal" class="modal">
    <div class="w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden rounded-lg bg-white">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <h3 id="marksTitle" class="font-semibold truncate"></h3>
            <button type="button" onclick="closeModal('marksModal')" class="text-zinc-400 hover:text-zinc-800 text-2xl leading-none" aria-label="Close">&times;</button>
        </div>

        <form action="save_marks.php" method="POST" class="flex flex-col md:flex-row flex-grow overflow-hidden">
            <input type="hidden" name="project_id" id="marksProjectId">

            <!-- Evidence -->
            <div class="md:w-5/12 overflow-y-auto border-b md:border-b-0 md:border-r border-zinc-200 bg-zinc-50 p-5 space-y-5 text-sm">
                <div>
                    <h4 class="font-semibold mb-1">Abstract</h4>
                    <p id="marksAbstract" class="max-h-28 overflow-y-auto text-zinc-600"></p>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">Links</h4>
                    <div id="marksLinks" class="space-y-1"></div>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">Screenshots and photos</h4>
                    <div id="marksImages" class="grid grid-cols-2 gap-2"></div>
                </div>
            </div>

            <!-- Scoring -->
            <div class="md:w-7/12 overflow-y-auto p-5 flex flex-col justify-between gap-5">
                <div class="space-y-5">
                    <div>
                        <label class="label" for="inputReport">Project report (shared by the whole team, max 50)</label>
                        <input type="number" id="inputReport" name="report_marks" step="0.5" min="0" max="50" oninput="calculateTotals()" class="field !w-32">
                    </div>

                    <div id="marksMembers" class="space-y-3"></div>

                    <div id="marksAudit" class="hidden rounded-md border border-amber-300 bg-amber-50 p-3">
                        <label class="label text-amber-900" for="marksAuditReason">These marks are finalized. Why are you changing them?</label>
                        <input type="text" name="audit_reason" id="marksAuditReason" class="field">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-zinc-200 pt-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_finalized" value="1" id="marksFinalized" class="h-4 w-4 rounded border-zinc-300 text-teal-700">
                        Finalize and lock marks
                    </label>
                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="closeModal('marksModal')" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save marks</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let marksMembers = [];

// project: entry from projectData
function openMarksModal(project) {
    marksMembers = project.members;

    document.getElementById('marksProjectId').value = project.id;
    document.getElementById('marksTitle').textContent = project.title;
    document.getElementById('marksAbstract').textContent = project.description || 'No abstract provided.';
    document.getElementById('inputReport').value = project.report_marks ?? '';
    document.getElementById('marksFinalized').checked = project.is_finalized == 1;
    document.getElementById('marksAudit').classList.toggle('hidden', project.is_finalized != 1);
    document.getElementById('marksAuditReason').value = '';

    const links = project.assets.filter(a => /^https?:\/\//i.test(a.external_url || ''));
    document.getElementById('marksLinks').innerHTML = links.length
        ? links.map(a => `<a href="${esc(a.external_url)}" target="_blank" rel="noopener" class="block truncate text-teal-700 hover:underline">${esc(a.title)}</a>`).join('')
        : '<p class="text-zinc-400">No links.</p>';

    const images = project.assets.filter(a => a.file_path);
    document.getElementById('marksImages').innerHTML = images.length
        ? images.map(a => `
            <button type="button" data-lightbox data-src="${esc(a.file_path)}" data-title="${esc(a.title)}" data-caption="${esc(a.caption)}"
                    class="relative h-20 overflow-hidden rounded border border-zinc-200 bg-zinc-200">
                <img src="${esc(a.file_path)}" alt="${esc(a.title)}" class="h-full w-full object-cover">
            </button>`).join('')
        : '<p class="col-span-2 text-zinc-400">No images.</p>';

    document.getElementById('marksMembers').innerHTML = project.members.map(m => `
        <div class="rounded-md border border-zinc-200 p-3">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">${esc(m.name)} <span class="font-normal text-zinc-500">(${esc(m.identifier)})</span>${m.is_leader ? ' <span class="tag tag-info">Leader</span>' : ''}</span>
                <span class="tabular-nums">Total <strong id="total_${m.id}">0.0</strong> / 100</span>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <div>
                    <label class="label text-xs">Presentation (max 25)</label>
                    <input type="number" id="pres_${m.id}" name="student_marks[${m.id}][presentation]" value="${m.presentation_marks ?? ''}" step="0.5" min="0" max="25" oninput="calculateTotals()" class="field">
                </div>
                <div>
                    <label class="label text-xs">Viva (max 25)</label>
                    <input type="number" id="qa_${m.id}" name="student_marks[${m.id}][qa]" value="${m.qa_marks ?? ''}" step="0.5" min="0" max="25" oninput="calculateTotals()" class="field">
                </div>
            </div>
        </div>`).join('');

    calculateTotals();
    openModal('marksModal');
}

function calculateTotals() {
    const value = id => parseFloat(document.getElementById(id).value) || 0;
    const report = value('inputReport');
    marksMembers.forEach(m => {
        document.getElementById('total_' + m.id).textContent = (report + value('pres_' + m.id) + value('qa_' + m.id)).toFixed(1);
    });
}
</script>
