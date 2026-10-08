<?php
// Phase 4 - teacher flexibility. Coordinators may rename derived phases or merge
// weeks (e.g. when a university schedule shifts a milestone onto another week).
// This is a form POST handler, not JSON: the Phases tab posts plain forms and
// bounces back to dashboard.php?tab=phases.

require_once 'bootstrap.php';
require_once 'phase_engine.php';
require_once 'repositories/project_repository.php';
require_once 'repositories/phase_repository.php';

require_login();

$classroom_id = $_POST['classroom_id'] ?? null;
$action       = $_POST['action'] ?? null;

function phase_back($classroom_id, $ok, $message)
{
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id)
        . "&tab=phases&" . ($ok ? 'msg=' : 'err=') . urlencode($message));
    exit;
}

if (!is_post()) {
    header("Location: dashboard.php");
    exit;
}
csrf_verify();

if (!$classroom_id || !in_array($action, ['rename', 'merge', 'unmerge'], true)) {
    header("Location: dashboard.php");
    exit;
}

// Only the classroom coordinator may reshape the schedule. Matches the
// $viewData['isCoordinator'] check in dashboard.php so the UI and the
// endpoint agree on who gets edit controls.
$classroom = classroom_find_schedule($pdo, $classroom_id);

if (!$classroom) {
    header("Location: hub.php");
    exit;
}
if (!is_classroom_coordinator($pdo, $classroom_id, $_SESSION['user_id'])) {
    phase_back($classroom_id, false, 'Only the classroom coordinator can rename or merge phases.');
}

$week_number = isset($_POST['week_number']) ? (int)$_POST['week_number'] : 0;
$totalWeeks  = phase_total_weeks($classroom['start_date'] ?? null, $classroom['end_date'] ?? null);

if ($totalWeeks < 1 || $week_number < 1 || $week_number > $totalWeeks) {
    phase_back($classroom_id, false, 'That phase does not exist in this classroom schedule.');
}

try {
    // Make sure every derived week has a row before we touch it. INSERT IGNORE
    // never overwrites a label the coordinator already set.
    phase_seed($pdo, $classroom_id, $classroom['start_date'] ?? null, $classroom['end_date'] ?? null);

    if (!phase_row_exists($pdo, $classroom_id, $week_number)) {
        phase_back($classroom_id, false, 'That phase does not exist in this classroom schedule.');
    }

    if ($action === 'rename') {
        $label = trim($_POST['label'] ?? '');
        if ($label === '') {
            phase_back($classroom_id, false, 'A phase needs a name.');
        }
        $label = mb_substr($label, 0, 120);

        phase_rename($pdo, $classroom_id, $week_number, $label);
        phase_back($classroom_id, true, 'Phase ' . $week_number . ' renamed to "' . $label . '".');
    }

    if ($action === 'unmerge') {
        phase_set_merge($pdo, $classroom_id, $week_number, null);
        phase_back($classroom_id, true, 'Phase ' . $week_number . ' is a standalone week again.');
    }

    // action === 'merge'
    $target = isset($_POST['target_week']) ? (int)$_POST['target_week'] : 0;

    if ($target < 1 || $target > $totalWeeks) {
        phase_back($classroom_id, false, 'Pick which existing phase this one should merge into.');
    }
    if ($target === $week_number) {
        phase_back($classroom_id, false, 'A phase cannot be merged into itself.');
    }

    // The target must be a real, standalone phase. Merging into a phase that is
    // itself merged away would build a chain that phase_build_list() cannot resolve.
    $targetMerge = phase_merge_target_of($pdo, $classroom_id, $target);
    if ($targetMerge === false) {
        phase_back($classroom_id, false, 'The phase you picked no longer exists.');
    }
    if ($targetMerge !== null && $targetMerge !== false) {
        phase_back($classroom_id, false, 'That phase is already merged elsewhere. Unmerge it first.');
    }

    // Merging a phase that already collects other phases would strand those
    // weeks: they would roll up into a target that is itself no longer a
    // standalone week. Force the coordinator to unmerge first.
    if (phase_absorbed_count($pdo, $classroom_id, $target) > 0) {
        phase_back($classroom_id, false, 'That phase has other weeks merged into it. Unmerge those first.');
    }

    phase_set_merge($pdo, $classroom_id, $week_number, $target);
    phase_back($classroom_id, true, 'Phase ' . $week_number . ' now rolls up into Phase ' . $target . '.');

} catch (PDOException $e) {
    // Most likely classroom_phases does not exist yet.
    phase_back($classroom_id, false, 'Phase overrides are unavailable until the Phase 4 migration has been imported.');
}