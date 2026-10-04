<?php
/**
 * "Students should abide the changes suggested by your guide" — compliance tracker.
 *
 * Requires a new small table, e.g.:
 *   CREATE TABLE revision_items (
 *     id INT PRIMARY KEY AUTO_INCREMENT,
 *     weekly_log_id INT NOT NULL,        -- FK to the log row the remark came from
 *     description VARCHAR(255) NOT NULL, -- one line per suggested change
 *     resolved TINYINT(1) DEFAULT 0,
 *     resolved_at DATETIME NULL,
 *     FOREIGN KEY (weekly_log_id) REFERENCES weekly_logs(id)
 *   );
 *
 * Populate rows when a mentor marks 'revision_needed' — either by having the
 * mentor list specific items in the review modal, or by letting the student
 * break the freeform mentor_remarks into checklist items themselves.
 *
 * Expects $viewData['revisionItems'] = [['id'=>.., 'description'=>.., 'resolved'=>bool, 'week'=>int], ...]
 */
$items = $viewData['revisionItems'] ?? [];
$total = count($items) ?: 1;
$resolved = count(array_filter($items, fn($i) => $i['resolved']));
$percent = (int)round(($resolved / $total) * 100);
?>
<div class="card p-5">
    <div class="flex justify-between items-baseline mb-3">
        <h3 class="font-semibold text-sm font-head">
            <i class="fas fa-list-check me-2" style="color: var(--accent-2);"></i>Guide's requested changes
        </h3>
        <span class="text-xs font-mono-ui text-muted-ui"><?php echo $resolved; ?>/<?php echo count($items); ?> addressed</span>
    </div>

    <div class="ink-bar-track w-full mb-4">
        <div class="ink-bar-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $percent === 100 ? 'var(--status-green)' : 'var(--accent-2)'; ?>;"></div>
    </div>

    <?php if (empty($items)): ?>
        <p class="text-sm text-muted-ui italic">No outstanding changes requested. Nice.</p>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($items as $item): ?>
            <label class="flex items-start gap-3 text-sm cursor-pointer select-none group">
                <input type="checkbox"
                       class="mt-0.5 w-4 h-4 cursor-pointer flex-shrink-0"
                       style="accent-color: var(--status-green);"
                       data-item-id="<?php echo (int)$item['id']; ?>"
                       onchange="toggleRevisionItem(this)"
                       <?php echo $item['resolved'] ? 'checked' : ''; ?>>
                <span class="<?php echo $item['resolved'] ? 'line-through text-muted-ui' : ''; ?>">
                    <?php echo htmlspecialchars($item['description']); ?>
                    <span class="text-[10px] text-muted-ui ml-1">(Week <?php echo (int)$item['week']; ?>)</span>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleRevisionItem(checkbox) {
    const fd = new FormData();
    fd.append('item_id', checkbox.dataset.itemId);
    fd.append('resolved', checkbox.checked ? '1' : '0');
    fetch('toggle_revision_item.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                checkbox.checked = !checkbox.checked; // revert on failure
                showToast('Could not update — try again.');
            }
        })
        .catch(() => { checkbox.checked = !checkbox.checked; });
}
</script>
