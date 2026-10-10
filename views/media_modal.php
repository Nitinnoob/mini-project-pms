<!-- views/media_modal.php - Lightbox Asset Viewer & Lightbox Modal -->
<div id="mediaLightboxModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative max-w-4xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-700 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 bg-slate-900 text-white">
            <div class="flex items-center space-x-3">
                <span id="lightboxTypeBadge" class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold uppercase tracking-wider bg-indigo-500/30 text-indigo-300 border border-indigo-400/30">
                    Screenshot
                </span>
                <h3 id="lightboxTitle" class="text-base font-bold truncate max-w-md">Asset Viewer</h3>
            </div>
            <button onclick="closeMediaLightbox()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Image Display Area -->
        <div class="flex-grow overflow-auto p-4 flex items-center justify-center bg-slate-950 min-h-[300px]">
            <img id="lightboxImage" src="" alt="Project Asset" class="max-h-[60vh] max-w-full object-contain rounded-lg shadow-lg">
        </div>

        <!-- Caption & Metadata Footer -->
        <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
            <div class="text-slate-600 flex-grow">
                <p id="lightboxCaption" class="italic text-slate-700">No caption provided.</p>
            </div>
            <div class="flex items-center space-x-2 flex-shrink-0">
                <a id="lightboxDownloadBtn" href="" target="_blank" download class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-colors inline-flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Open Full Image
                </a>
                <button onclick="closeMediaLightbox()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openMediaLightbox(src, title, type, caption) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('lightboxDownloadBtn').href = src;
    document.getElementById('lightboxTitle').textContent = title || 'Project Asset';
    document.getElementById('lightboxTypeBadge').textContent = type || 'Asset';
    document.getElementById('lightboxCaption').textContent = caption || 'No additional caption notes.';
    document.getElementById('mediaLightboxModal').classList.remove('hidden');
}

function closeMediaLightbox() {
    document.getElementById('mediaLightboxModal').classList.add('hidden');
    document.getElementById('lightboxImage').src = '';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMediaLightbox();
        if (typeof closeAttendanceModal === 'function') closeAttendanceModal();
        if (typeof closeMarksModal === 'function') closeMarksModal();
    }
});
</script>
