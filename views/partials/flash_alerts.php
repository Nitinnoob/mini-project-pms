<?php
// Redirect outcome banners (error keys and phase-edit notices).
$errorMessages = [
    'min_team_size' => 'Final week log blocked: your team has fewer active members than this classroom\'s minimum team size. Invite classmates from the Team tab before submitting.',
    'log_approved'  => 'That weekly log has already been approved by your mentor and can no longer be edited.',
];
$errorKey = $_GET['error'] ?? null;
if ($errorKey && isset($errorMessages[$errorKey])):
    $msg = e($errorMessages[$errorKey]);
?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: var(--danger);">
        <i class="fas fa-circle-exclamation mt-0.5 flex-none" style="color: var(--danger);"></i>
        <div>
            <h4 class="font-semibold text-sm font-head">Action blocked</h4>
            <p class="text-sm text-muted-ui mt-1"><?php echo $msg; ?></p>
        </div>
    </div>
</div>
<?php endif;

// Phase edits redirect back with a one-line outcome instead of a typed error key.
$phaseNotice = trim($_GET['msg'] ?? '') !== '' ? e($_GET['msg']) : null;
$phaseError  = trim($_GET['err'] ?? '') !== '' ? e($_GET['err']) : null;
if ($phaseNotice || $phaseError):
    $tone = $phaseError ? 'var(--danger)' : 'var(--accent)';
?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: <?php echo $tone; ?>;">
        <i class="fas <?php echo $phaseError ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> mt-0.5 flex-none" style="color: <?php echo $tone; ?>;"></i>
        <div>
            <?php if ($phaseError): ?>
                <h4 class="font-semibold text-sm font-head">Could not update the schedule</h4>
            <?php endif; ?>
            <p class="text-sm text-muted-ui mt-1"><?php echo $phaseError ?? $phaseNotice; ?></p>
        </div>
    </div>
</div>
<?php endif;
