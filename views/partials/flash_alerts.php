<?php
// Redirect outcome banners (error keys and session flashes).
$errorMessages = [
    'min_team_size'   => 'Final week log blocked: your team has fewer active members than this classroom\'s minimum team size. Invite classmates from the Team tab before submitting.',
    'log_approved'    => 'That weekly log has already been approved by your mentor and can no longer be edited.',
    'user_is_student' => 'This user is already enrolled as a student in this classroom and cannot be added as a mentor.',
];

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$errorKey = $_GET['error'] ?? null;
if ($errorKey && isset($errorMessages[$errorKey])) {
    $flashError = $errorMessages[$errorKey];
}
?>
<?php if ($flashError): ?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: var(--danger); background-color: rgba(var(--danger-rgb), 0.05);">
        <i class="fas fa-circle-exclamation mt-0.5 flex-none" style="color: var(--danger);"></i>
        <div>
            <h4 class="font-semibold text-sm font-head" style="color: var(--danger);">Action blocked</h4>
            <p class="text-sm text-muted-ui mt-1"><?php echo e($flashError); ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: var(--status-green); background-color: rgba(var(--status-green-rgb), 0.05);">
        <i class="fas fa-circle-check mt-0.5 flex-none" style="color: var(--status-green);"></i>
        <div>
            <h4 class="font-semibold text-sm font-head" style="color: var(--status-green);">Success</h4>
            <p class="text-sm text-muted-ui mt-1"><?php echo e($flashSuccess); ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

