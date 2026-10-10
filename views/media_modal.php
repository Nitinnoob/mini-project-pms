<!-- views/media_modal.php - image lightbox. Open with any element carrying data-lightbox, data-src, data-title, data-caption -->
<div id="mediaModal" class="modal">
    <div class="w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden rounded-lg bg-white">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-200">
            <h3 id="mediaTitle" class="font-semibold truncate"></h3>
            <button type="button" onclick="closeModal('mediaModal')" class="text-zinc-400 hover:text-zinc-800 text-2xl leading-none" aria-label="Close">&times;</button>
        </div>

        <div class="flex justify-center overflow-auto bg-zinc-900 p-4">
            <img id="mediaImage" src="" alt="" class="max-h-[60vh] max-w-full object-contain">
        </div>

        <div class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
            <p id="mediaCaption" class="text-zinc-600"></p>
            <a id="mediaLink" href="" target="_blank" rel="noopener" class="btn shrink-0">Open full image</a>
        </div>
    </div>
</div>

<script>
function openMediaModal(src, title, caption) {
    document.getElementById('mediaImage').src = src;
    document.getElementById('mediaImage').alt = title || '';
    document.getElementById('mediaLink').href = src;
    document.getElementById('mediaTitle').textContent = title || 'Project asset';
    document.getElementById('mediaCaption').textContent = caption || 'No caption.';
    openModal('mediaModal');
}

document.addEventListener('click', e => {
    const el = e.target.closest('[data-lightbox]');
    if (el) openMediaModal(el.dataset.src, el.dataset.title, el.dataset.caption);
});
</script>
