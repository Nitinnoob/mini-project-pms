<!-- views/marks_modal.php - Live Calculation Scoring Modal with Side-by-Side Asset Preview -->
<div id="marksModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6">
    <div class="relative max-w-5xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-300 flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 bg-slate-900 text-white">
            <div class="flex items-center space-x-3">
                <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold uppercase tracking-wider bg-indigo-500/30 text-indigo-300 border border-indigo-400/30">
                    CIE 50/25/25 Scheme
                </span>
                <h3 id="marksModalProjectTitle" class="text-base font-bold truncate max-w-md">Enter CIE Marks</h3>
            </div>
            <button onclick="closeMarksModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="marksForm" action="save_marks.php" method="POST" class="flex-grow flex flex-col md:flex-row overflow-hidden">
            <input type="hidden" name="project_id" id="marksProjectId" value="">

            <!-- Left Panel: Project Asset Preview & Evidence Gallery -->
            <div class="md:w-5/12 bg-slate-50 border-r border-slate-200 p-5 overflow-y-auto space-y-4">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Project Abstract</h4>
                    <p id="marksModalAbstract" class="text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-200 leading-relaxed max-h-24 overflow-y-auto">
                        Abstract details...
                    </p>
                </div>

                <!-- Codebase & Demo Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Technical Deliverables & Repositories</h4>
                    <div id="marksModalLinks" class="space-y-1.5 text-xs">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Visual Media Thumbnails for Side-by-Side Review -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Prototype & UI Evidence Gallery</h4>
                    <div id="marksModalThumbnails" class="grid grid-cols-2 gap-2">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Right Panel: Scoring Engine & Live Computations -->
            <div class="md:w-7/12 p-6 overflow-y-auto space-y-5 flex flex-col justify-between">
                <div class="space-y-4">
                    <!-- Shared Team Score: Project Report & Documentation (Max 50) -->
                    <div class="bg-indigo-50/70 border border-indigo-200 p-4 rounded-xl">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <label class="text-xs font-bold text-indigo-950 uppercase tracking-wider">
                                    Project Report & Documentation (Shared Team Score)
                                </label>
                                <p class="text-[11px] text-indigo-700">Applies uniformly across all project team members.</p>
                            </div>
                            <span class="text-xs font-bold text-indigo-900 bg-white px-2 py-0.5 rounded border border-indigo-200">Max: 50.0</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input type="number" step="0.5" min="0" max="50" name="report_marks" id="inputReportMarks" oninput="calculateTotals()" placeholder="0.0 - 50.0" class="w-36 px-3 py-2 text-sm font-mono font-bold border border-indigo-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                            <span class="text-xs font-semibold text-indigo-800">/ 50.0 Marks</span>
                        </div>
                    </div>

                    <!-- Individual Student Scores (Presentation / 25, Viva / 25) -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Individual Student Assessment (Pres: 25 | Viva: 25)
                        </h4>
                        <div id="marksStudentsContainer" class="space-y-3">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Audit Trail Box (For Post-Finalization Changes) -->
                    <div id="marksAuditBox" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs hidden">
                        <label class="block font-bold text-amber-900 mb-1 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Marks Modification Justification (Mandatory for Finalized Records)
                        </label>
                        <input type="text" name="audit_reason" id="marksAuditReason" placeholder="State formal reason for post-finalization mark change (recorded in marks_changes)" class="w-full px-3 py-2 text-xs border border-amber-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                    </div>
                </div>

                <!-- Footer Action Area with Finalize Lock -->
                <div class="pt-4 border-t border-slate-200 mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <label class="flex items-center space-x-2 text-xs cursor-pointer select-none">
                        <input type="checkbox" name="is_finalized" value="1" id="checkFinalizeMarks" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span class="font-bold text-slate-800">Finalize & Lock CIE Marks</span>
                        <span class="text-[11px] text-slate-500">(Prevents accidental alteration)</span>
                    </label>

                    <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                        <button type="button" onclick="closeMarksModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-md transition-colors">
                            Save CIE Marks
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let currentProjectStudents = [];

function openMarksModal(data) {
    document.getElementById('marksProjectId').value = data.project_id;
    document.getElementById('marksModalProjectTitle').textContent = data.title;
    document.getElementById('marksModalAbstract').textContent = data.description || 'No detailed technical abstract specified.';
    document.getElementById('inputReportMarks').value = (data.report_marks !== null && data.report_marks !== undefined) ? data.report_marks : '';
    document.getElementById('checkFinalizeMarks').checked = (data.is_finalized == 1);

    // Audit box
    const auditBox = document.getElementById('marksAuditBox');
    if (data.is_finalized == 1) {
        auditBox.classList.remove('hidden');
    } else {
        auditBox.classList.add('hidden');
    }

    // Populate deliverable links
    const linksContainer = document.getElementById('marksModalLinks');
    linksContainer.innerHTML = '';
    if (!data.assets || data.assets.length === 0) {
        linksContainer.innerHTML = '<p class="text-slate-400 italic">No assets or links uploaded yet.</p>';
    } else {
        data.assets.forEach(a => {
            if (a.external_url) {
                const aLink = document.createElement('a');
                aLink.href = a.external_url;
                aLink.target = '_blank';
                aLink.className = 'block p-2 bg-white rounded-lg border border-slate-200 hover:border-indigo-400 text-indigo-600 hover:text-indigo-800 font-semibold truncate flex items-center space-x-1.5';
                aLink.innerHTML = `
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>${a.title}</span>
                `;
                linksContainer.appendChild(aLink);
            }
        });
    }

    // Populate visual image thumbnails
    const thumbContainer = document.getElementById('marksModalThumbnails');
    thumbContainer.innerHTML = '';
    let hasMedia = false;
    if (data.assets) {
        data.assets.forEach(a => {
            if (a.file_path) {
                hasMedia = true;
                const card = document.createElement('div');
                card.className = 'group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-900 cursor-pointer h-20';
                card.onclick = () => openMediaLightbox(a.file_path, a.title, a.asset_type, a.caption);
                card.innerHTML = `
                    <img src="${a.file_path}" alt="${a.title}" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'400\\' height=\\'250\\' viewBox=\\'0 0 400 250\\'><rect fill=\\'%230f172a\\' width=\\'400\\' height=\\'250\\'/><text fill=\\'%2394a3b8\\' x=\\'50%25\\' y=\\'50%25\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-family=\\'sans-serif\\' font-size=\\'14\\'>Visual Project Evidence</text></svg>';" class="w-full h-full object-cover group-hover:opacity-80 transition-opacity">
                    <span class="absolute bottom-0 inset-x-0 bg-slate-900/80 text-[10px] text-white p-1 truncate text-center">${a.title}</span>
                `;
                thumbContainer.appendChild(card);
            }
        });
    }
    if (!hasMedia) {
        thumbContainer.innerHTML = '<p class="col-span-2 text-slate-400 italic text-[11px]">No prototype screenshots uploaded.</p>';
    }

    // Populate individual student scoring rows
    currentProjectStudents = data.members;
    const sContainer = document.getElementById('marksStudentsContainer');
    sContainer.innerHTML = '';

    data.members.forEach(m => {
        const row = document.createElement('div');
        row.className = 'p-3 bg-white border border-slate-200 rounded-xl space-y-2';

        const presVal = (m.presentation_marks !== null && m.presentation_marks !== undefined) ? m.presentation_marks : '';
        const qaVal   = (m.qa_marks !== null && m.qa_marks !== undefined) ? m.qa_marks : '';

        row.innerHTML = `
            <div class="flex items-center justify-between text-xs">
                <div>
                    <span class="font-bold text-slate-800">${m.name}</span>
                    <span class="font-mono text-slate-500 ml-1">(${m.identifier})</span>
                    ${m.is_leader ? '<span class="text-[9px] bg-indigo-50 text-indigo-700 px-1 rounded border border-indigo-200 font-semibold ml-1">Leader</span>' : ''}
                </div>
                <div class="font-bold text-xs">
                    Total: <span id="studentTotal_${m.id}" class="text-indigo-600 font-mono text-sm">0.0</span> / 100
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-semibold">Presentation & Demo (Max 25)</label>
                    <input type="number" step="0.5" min="0" max="25" name="student_marks[${m.id}][presentation]" id="inputPres_${m.id}" value="${presVal}" oninput="calculateTotals()" placeholder="0 - 25" class="w-full px-2.5 py-1.5 font-mono text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-semibold">Viva Voce & Q&A (Max 25)</label>
                    <input type="number" step="0.5" min="0" max="25" name="student_marks[${m.id}][qa]" id="inputQa_${m.id}" value="${qaVal}" oninput="calculateTotals()" placeholder="0 - 25" class="w-full px-2.5 py-1.5 font-mono text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
        `;

        sContainer.appendChild(row);
    });

    calculateTotals();
    document.getElementById('marksModal').classList.remove('hidden');
}

function calculateTotals() {
    const reportMarks = parseFloat(document.getElementById('inputReportMarks').value) || 0;

    currentProjectStudents.forEach(m => {
        const presInput = document.getElementById(`inputPres_${m.id}`);
        const qaInput   = document.getElementById(`inputQa_${m.id}`);

        const pres = presInput ? (parseFloat(presInput.value) || 0) : 0;
        const qa   = qaInput   ? (parseFloat(qaInput.value) || 0) : 0;

        const total = (reportMarks + pres + qa).toFixed(1);
        const totalSpan = document.getElementById(`studentTotal_${m.id}`);
        if (totalSpan) {
            totalSpan.textContent = total;
        }
    });
}

function closeMarksModal() {
    document.getElementById('marksModal').classList.add('hidden');
}
</script>
