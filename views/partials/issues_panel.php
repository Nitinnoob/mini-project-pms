<?php
/**
 * Open blockers panel (Student / Leader / Teacher drilldown).
 * Data: $viewData['openIssues'], $viewData['canResolveIssues'].
 */
$openIssues = $viewData['openIssues'] ?? [];
if (!empty($openIssues)):
    $sevTone = ['critical' => 'var(--danger)', 'high' => 'var(--danger)', 'medium' => 'var(--status-amber)', 'low' => 'var(--muted)'];
?>
<div class="card p-4" style="border-top: 3px solid var(--danger);">
    <h3 class="font-head font-semibold text-sm mb-3 flex items-center gap-2">
        <i class="fas fa-triangle-exclamation text-danger"></i> Open blockers
        <span class="badge badge-danger"><?php echo count($openIssues); ?></span>
    </h3>
    <div class="space-y-3">
        <?php foreach ($openIssues as $iss): ?>
        <div class="flex items-start justify-between gap-3 p-3 bg-raised border border-ui rounded">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color: <?php echo $sevTone[$iss['severity']] ?? 'var(--muted)'; ?>;"><?php echo e($iss['severity']); ?></span>
                    <span class="font-semibold text-sm"><?php echo e($iss['title']); ?></span>
                </div>
                <p class="text-sm text-muted-ui mt-1"><?php echo nl2br(e($iss['description'])); ?></p>
                <p class="text-xs text-muted-ui mt-1">
                    Raised by <?php echo e($iss['raised_by_name']); ?> &middot; <?php echo e(date('M j, g:i A', strtotime($iss['created_at']))); ?>
                    <?php if (!empty($iss['phase_label'])): ?> &middot; <?php echo e($iss['phase_label']); ?><?php endif; ?>
                    <?php if (!empty($iss['task_title'])): ?> &middot; Task: <?php echo e($iss['task_title']); ?><?php endif; ?>
                </p>
            </div>
            <?php if (!empty($viewData['canResolveIssues'])): ?>
            <form method="POST" action="resolve_issue.php" class="flex-none">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="issue_id" value="<?php echo (int)$iss['id']; ?>">
                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
                <input type="hidden" name="back_project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                <?php endif; ?>
                <button class="btn-ui px-3 py-1.5 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                    <i class="fas fa-check me-1"></i>Resolve
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
