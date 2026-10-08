<?php
/**
 * Shared Weekly Logs view (used by Student & Project Leader).
 * All data arrives pre-built in $viewData — this partial never queries.
 *
 * Designed for maximum readability:
 * - Focuses cleanly on the Current Week by default with an intuitive week navigator.
 * - Simple "Week N" naming without prescriptive week labels.
 * - Clean, spacious typography (text-base, text-sm, high contrast, breathable padding).
 */
$canSubmit = !empty($viewData['canSubmitWeeklyUpdate']) && empty($viewData['isTeacherDrilldown']);
$isMentor  = !empty($viewData['isTeacherDrilldown'])
             && (!empty($viewData['isCoordinator']) || (($viewData['myProject']['mentor_id'] ?? 0) == $_SESSION['user_id']));
$rolloverInstructions = $viewData['rolloverInstructions'] ?? [];

// Determine default selected week: current week if available, else week 1
$defaultWeek = 1;
if (!empty($viewData['logWeeks'])) {
    foreach ($viewData['logWeeks'] as $lw) {
        if (!empty($lw['is_current'])) {
            $defaultWeek = (int)$lw['week_number'];
            break;
        }
        $defaultWeek = (int)$lw['week_number'];
    }
}
?>

<div class="card p-6 flex-1 overflow-y-auto flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h3 class="font-head font-bold text-xl text-white">Saturday Guide Review &amp; Weekly Log</h3>
            <p class="text-sm text-muted-ui mt-0.5">
                Weekly progress record, mentor feedback, guide attendance &amp; directives
            </p>
        </div>
        <?php if (!$canSubmit && !$isMentor && !$viewData['hasSchedule']): ?>
            <span class="text-xs text-muted-ui">Logs are disabled until the coordinator sets start and end dates.</span>
        <?php elseif ($canSubmit): ?>
            <span class="text-xs px-3 py-1.5 rounded bg-overlay-subtle border border-ui text-muted-ui flex items-center gap-1.5 font-medium">
                <i class="fas fa-users text-accent"></i> Any active team member can submit weekly updates
            </span>
        <?php elseif (!$canSubmit && !$isMentor): ?>
            <span class="text-xs px-3 py-1.5 rounded bg-overlay-subtle border border-ui text-muted-ui flex items-center gap-1">
                <i class="fas fa-eye"></i> Read-only view
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$viewData['hasSchedule']): ?>
        <div class="py-16 text-center text-muted-ui text-base italic border border-ui rounded-lg bg-raised">
            The classroom coordinator has not set start and end dates. Weekly logs are disabled.
        </div>
    <?php else: ?>

        <!-- Action Items Rollover (Prior Mentor Instructions) -->
        <?php if (!empty($rolloverInstructions)): ?>
        <div class="mb-6 p-5 rounded-lg border border-amber-500/40 bg-amber-950/20">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-sm font-bold">
                        <i class="fas fa-tasks"></i>
                    </span>
                    <h4 class="font-head font-bold text-base text-amber-200">
                        Action Items Rollover (Prior Mentor Instructions)
                    </h4>
                    <span class="badge badge-accent text-xs font-semibold"><?php echo count($rolloverInstructions); ?> open</span>
                </div>
                <span class="text-xs text-muted-ui">
                    Instructions persist across weeks until marked done by your guide.
                </span>
            </div>

            <div class="space-y-3 mt-2">
                <?php foreach ($rolloverInstructions as $inst):
                    $isAck = ($inst['status'] === 'acknowledged');
                ?>
                <div class="flex items-center justify-between p-4 rounded-lg bg-raised border border-ui text-sm flex-wrap gap-3 shadow-sm">
                    <div class="flex-1 min-w-[260px]">
                        <div class="flex items-center gap-2.5 mb-1.5 flex-wrap">
                            <span class="font-mono-ui font-semibold text-xs px-2 py-0.5 rounded bg-overlay-medium text-white">
                                Week <?php echo (int)$inst['week_number']; ?>
                            </span>
                            <?php if ($isAck): ?>
                                <span class="badge badge-accent text-xs"><i class="fas fa-check mr-1"></i>Acknowledged</span>
                            <?php else: ?>
                                <span class="badge badge-danger text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Action Required</span>
                            <?php endif; ?>
                            <span class="text-muted-ui text-xs">from <?php echo e($inst['created_by_name']); ?></span>
                        </div>
                        <div class="font-medium text-base text-text leading-relaxed"><?php echo nl2br(e($inst['text'])); ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($canSubmit && !$isAck): ?>
                            <form method="POST" action="manage_instruction.php" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="acknowledge">
                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                <button type="submit" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5 text-accent border border-accent">
                                    <i class="fas fa-check"></i> Acknowledge
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($isMentor): ?>
                            <form method="POST" action="manage_instruction.php" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_done">
                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                <button type="submit" class="btn-ui px-3.5 py-1.5 text-xs font-semibold transition flex items-center gap-1.5 shadow-sm" style="background: var(--accent-2); color: var(--bg);">
                                    <i class="fas fa-check-double"></i> Mark Done
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Horizontal Week Selector -->
        <div class="flex items-center gap-2.5 mb-6 overflow-x-auto pb-2">
            <?php foreach ($viewData['logWeeks'] as $p):
                $wk = (int)$p['week_number'];
                $isCurrent = !empty($p['is_current']);
                $log = $viewData['weeklyLogs'][$wk] ?? null;
                $hasLog = $log && (!empty($log['work_summary']) || !empty($log['meeting_status']));
                $mStat = $log['meeting_status'] ?? ($log['status'] ?? 'scheduled');
            ?>
                <button type="button"
                        onclick="switchLogWeek(<?php echo $wk; ?>)"
                        id="week-nav-btn-<?php echo $wk; ?>"
                        class="week-nav-btn px-4 py-2.5 rounded-lg border text-sm font-semibold transition flex items-center gap-2 flex-none
                               <?php echo ($wk === $defaultWeek) ? 'border-accent bg-overlay-medium text-white ring-1 ring-accent' : 'border-ui bg-raised text-muted-ui hover:text-white hover:border-accent'; ?>"
                        style="<?php echo ($wk === $defaultWeek) ? 'border-color: var(--accent);' : ''; ?>">
                    <span>Week <?php echo $wk; ?></span>
                    <?php if ($isCurrent): ?>
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase" style="background: var(--accent-2); color: var(--bg);">Current</span>
                    <?php elseif ($mStat === 'held'): ?>
                        <i class="fas fa-check-circle text-xs text-emerald-400"></i>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Weekly Log Content Panes (Only 1 active week displayed at a time) -->
        <?php foreach ($viewData['logWeeks'] as $p):
            $wk  = (int)$p['week_number'];
            $log = $viewData['weeklyLogs'][$wk] ?? null;
            $mStatus = $log['meeting_status'] ?? ($log['status'] ?? 'scheduled');
            $reviewStatus = $log ? ($log['review_status'] ?? 'pending') : 'unsubmitted';

            $meetingBadge = match ($mStatus) {
                'held'        => '<span class="badge badge-green text-sm px-3 py-1"><i class="fas fa-check-circle mr-1.5"></i>Meeting Held</span>',
                'rescheduled' => '<span class="badge badge-accent text-sm px-3 py-1"><i class="fas fa-redo-alt mr-1.5"></i>Rescheduled</span>',
                'holiday'     => '<span class="badge badge-muted text-sm px-3 py-1"><i class="fas fa-umbrella-beach mr-1.5"></i>Holiday</span>',
                default       => '<span class="badge badge-muted text-sm px-3 py-1"><i class="fas fa-calendar-alt mr-1.5"></i>Scheduled</span>',
            };

            $meetingData = [
                'id'                 => $log['meeting_id'] ?? ($log['id'] ?? 0),
                'week_number'        => $wk,
                'meeting_date'       => $log['meeting_date'] ?? $p['date_to'],
                'status'             => $mStatus,
                'attendance'         => $log['attendance'] ?? [],
                'attendance_changes' => $log['attendance_changes'] ?? [],
            ];

            $hasUpdate = $log && (!empty($log['work_summary']) || !empty($log['next_steps']) || !empty($log['blockers']) || !empty($log['files']));
            $instructions = $log['instructions'] ?? [];
            $feedbackHistory = $log['feedback_history'] ?? [];
        ?>
        <div id="week-pane-<?php echo $wk; ?>" class="week-log-pane <?php echo ($wk === $defaultWeek) ? '' : 'hidden'; ?> flex flex-col space-y-5">
            
            <!-- Week Header Banner -->
            <div class="flex justify-between items-center p-5 rounded-lg bg-raised border border-ui flex-wrap gap-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <h4 class="font-bold text-xl text-white">Week <?php echo $wk; ?></h4>
                    <span class="text-sm text-muted-ui font-mono-ui bg-overlay-subtle px-3 py-1 rounded border border-ui">
                        Saturday &middot; <?php echo e(date('D, M j, Y', strtotime($p['date_to']))); ?>
                    </span>
                    <?php if (!empty($p['is_current'])): ?>
                        <span class="badge font-semibold text-xs px-2.5 py-1" style="background: var(--accent-2); color: var(--bg);">Current Week</span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php echo $meetingBadge; ?>
                </div>
            </div>

            <?php if (!$hasUpdate && empty($log['attendance']) && empty($log['mentor_remarks']) && empty($instructions)): ?>
                <div class="p-8 text-center rounded-lg bg-raised border border-ui">
                    <i class="fas fa-clipboard-list text-3xl text-muted-ui/40 mb-3"></i>
                    <p class="text-base text-text font-medium">No team update or guide review recorded for Week <?php echo $wk; ?> yet.</p>
                    <p class="text-sm text-muted-ui mt-1">Submit your weekly progress report before Saturday's review meeting.</p>
                </div>
            <?php else: ?>

                <!-- Submitter Attribution -->
                <?php if (!empty($log['submitted_by_name']) || !empty($log['submitted_at'])): ?>
                    <div class="text-xs text-muted-ui flex items-center gap-2 font-mono-ui px-1">
                        <i class="fas fa-user-edit text-accent"></i>
                        <span>Submitted by <strong class="text-white"><?php echo e($log['submitted_by_name'] ?? 'Team Member'); ?></strong></span>
                        <?php if (!empty($log['submitted_at'])): ?>
                            <span>&middot; <?php echo e(date('M j, Y g:i A', strtotime($log['submitted_at']))); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Work Accomplished -->
                <?php if (!empty($log['work_summary'])): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-2 mb-2">
                            <i class="fas fa-check-circle text-base"></i> Work Accomplished this Week
                        </span>
                        <div class="text-base text-text leading-relaxed whitespace-pre-line pl-1"><?php echo e($log['work_summary']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Next Steps -->
                <?php if (!empty($log['next_steps'])): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <span class="text-xs font-bold uppercase tracking-wider text-accent flex items-center gap-2 mb-2">
                            <i class="fas fa-arrow-circle-right text-base"></i> Next Steps for Upcoming Saturday
                        </span>
                        <div class="text-base text-text leading-relaxed whitespace-pre-line pl-1"><?php echo e($log['next_steps']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Blockers & Impediments -->
                <?php if (!empty($log['blockers'])): ?>
                    <div class="rounded-lg bg-amber-950/20 border border-amber-500/40 p-5">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-2 mb-2">
                            <i class="fas fa-exclamation-triangle text-base"></i> Blockers &amp; Impediments
                        </span>
                        <div class="text-base text-amber-100 leading-relaxed whitespace-pre-line pl-1"><?php echo e($log['blockers']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- File Attachments -->
                <?php if (!empty($log['files'])): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-ui flex items-center gap-2 mb-3">
                            <i class="fas fa-paperclip text-base"></i> Attached Artifacts &amp; Documents
                        </span>
                        <div class="flex flex-wrap gap-2.5">
                            <?php foreach ($log['files'] as $f):
                                $fName = $f['original_name'] ?? ($f['file_name'] ?? 'Attachment');
                                $fSizeStr = !empty($f['file_size']) ? ' (' . round($f['file_size'] / 1024) . ' KB)' : '';
                            ?>
                                <a href="<?php echo e($f['file_path']); ?>" download
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-overlay-subtle border border-ui hover:border-accent transition text-text hover:text-accent font-mono-ui">
                                    <i class="fas fa-file-download text-accent"></i>
                                    <span><?php echo e($fName) . $fSizeStr; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Meeting Action Items / Instructions -->
                <?php if (!empty($instructions)): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <span class="font-bold text-sm flex items-center gap-2 text-white">
                                <i class="fas fa-tasks text-accent"></i> Meeting Action Items &amp; Instructions
                            </span>
                            <span class="text-xs text-muted-ui font-mono-ui"><?php echo count($instructions); ?> item(s)</span>
                        </div>
                        <div class="space-y-2.5">
                            <?php foreach ($instructions as $inst):
                                $iStatus = $inst['status'];
                            ?>
                                <div class="flex items-center justify-between p-3.5 rounded-lg bg-overlay-subtle border border-ui text-sm flex-wrap gap-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-[240px]">
                                        <?php if ($iStatus === 'done'): ?>
                                            <span class="badge badge-green text-xs"><i class="fas fa-check-double mr-1"></i>Done</span>
                                        <?php elseif ($iStatus === 'acknowledged'): ?>
                                            <span class="badge badge-accent text-xs"><i class="fas fa-check mr-1"></i>Acknowledged</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Open</span>
                                        <?php endif; ?>
                                        <span class="text-text font-medium text-base"><?php echo e($inst['text']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if ($canSubmit && $iStatus === 'open'): ?>
                                            <form method="POST" action="manage_instruction.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="acknowledge">
                                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                                <button type="submit" class="btn-ui px-3 py-1 text-xs font-semibold text-accent border border-accent hover-overlay-medium">
                                                    Acknowledge
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($isMentor && $iStatus !== 'done'): ?>
                                            <form method="POST" action="manage_instruction.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="mark_done">
                                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                                <button type="submit" class="btn-ui px-3 py-1 text-xs font-semibold text-emerald-400 border border-emerald-500/40 hover-overlay-medium">
                                                    Mark Done
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Guide Feedback History Timeline -->
                <?php if (!empty($feedbackHistory)): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-bold text-sm flex items-center gap-2 text-white">
                                <i class="fas fa-comment-dots text-accent"></i> Guide Feedback History
                            </span>
                            <span class="text-xs text-muted-ui font-mono-ui"><?php echo count($feedbackHistory); ?> review(s)</span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($feedbackHistory as $fb):
                                $fbStatus = $fb['status'] ?? 'held';
                                $fbBadge = match ($fbStatus) {
                                    'approved'        => '<span class="badge badge-green text-xs">Approved</span>',
                                    'revision_needed' => '<span class="badge badge-danger text-xs">Revision Flagged</span>',
                                    default           => '<span class="badge badge-accent text-xs">Feedback</span>',
                                };
                            ?>
                                <div class="p-4 rounded-lg bg-overlay-subtle border border-ui text-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2.5">
                                            <strong class="text-text font-semibold"><?php echo e($fb['reviewer_name'] ?? 'Guide'); ?></strong>
                                            <?php echo $fbBadge; ?>
                                        </div>
                                        <span class="text-xs text-muted-ui font-mono-ui">
                                            <?php echo e(date('M j, Y g:i A', strtotime($fb['created_at'] ?? 'now'))); ?>
                                        </span>
                                    </div>
                                    <div class="text-text text-base leading-relaxed"><?php echo nl2br(e($fb['feedback'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif (!empty($log['mentor_remarks'])): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <span class="font-bold text-sm flex items-center gap-2 text-white mb-2">
                            <i class="fas fa-comment-dots text-muted-ui"></i> Guide Feedback
                        </span>
                        <p class="text-base text-text leading-relaxed"><?php echo nl2br(e($log['mentor_remarks'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Meeting Attendance -->
                <?php if (!empty($log['attendance'])): ?>
                    <div class="rounded-lg bg-raised border border-ui p-5">
                        <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                            <span class="font-bold text-sm flex items-center gap-2 text-white">
                                <i class="fas fa-users text-muted-ui"></i> Saturday Guide Meeting Attendance
                            </span>
                            <?php if ($mStatus === 'holiday' || $mStatus === 'rescheduled'): ?>
                                <span class="text-xs text-muted-ui italic">(No absences counted for <?php echo e(ucfirst($mStatus)); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($log['attendance'] as $att):
                                $attStatus = $att['status'] ?? ($att['present'] ? 'present' : 'absent');
                            ?>
                                <div class="flex justify-between items-center text-sm p-3 rounded-lg bg-overlay-subtle border border-ui">
                                    <span class="font-medium truncate mr-2"><?php echo e($att['username'] ?? 'Student'); ?></span>
                                    <?php if ($attStatus === 'present'): ?>
                                        <span class="font-semibold flex items-center gap-1.5 text-emerald-400">
                                            <i class="fas fa-check"></i> Present
                                        </span>
                                    <?php elseif ($attStatus === 'excused'): ?>
                                        <span class="font-semibold flex items-center gap-1.5 text-amber-400">
                                            <i class="fas fa-shield-alt"></i> Excused
                                        </span>
                                    <?php else: ?>
                                        <span class="font-semibold flex items-center gap-1.5 text-rose-400">
                                            <i class="fas fa-times"></i> Absent
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($log['attendance_changes'])): ?>
                            <div class="mt-3.5 pt-3 border-t border-ui text-xs text-muted-ui">
                                <span class="font-semibold text-accent"><i class="fas fa-history mr-1"></i>Attendance Audit Trail:</span>
                                <div class="space-y-1.5 mt-1.5">
                                    <?php foreach ($log['attendance_changes'] as $chg): ?>
                                        <div>
                                            &bull; <strong><?php echo e($chg['username']); ?></strong>:
                                            <span class="font-mono-ui"><?php echo e($chg['old_status']); ?> &rarr; <?php echo e($chg['new_status']); ?></span>
                                            by <?php echo e($chg['changed_by_name']); ?>
                                            &ndash; &ldquo;<?php echo e($chg['reason']); ?>&rdquo;
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Submit Weekly Update Button (Student / Leader) -->
            <?php if ($canSubmit && (!$log || $reviewStatus === 'revision_needed' || empty($log['work_summary']))):
                $prefillJson = json_encode([
                    'work_done'  => $log['work_summary'] ?? '',
                    'next_steps' => $log['next_steps'] ?? '',
                    'blockers'   => $log['blockers'] ?? '',
                ]);
            ?>
                <div class="pt-2">
                    <button type="button"
                            onclick="openWeeklyLogModal(<?php echo $wk; ?>, <?php echo htmlspecialchars($prefillJson, ENT_QUOTES, 'UTF-8'); ?>)"
                            class="btn-ui px-5 py-2.5 text-sm font-semibold hover-overlay-medium transition flex items-center gap-2"
                            style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-upload"></i>
                        <?php echo ($hasUpdate && !empty($log['work_summary'])) ? 'Submit Revision for Week ' . $wk : 'Submit Week ' . $wk . ' Meeting Update'; ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Guide Actions (Attendance & Feedback modals) -->
            <?php if ($isMentor):
                $logJson = $log ? json_encode([
                    'status'     => $log['review_status'] ?? 'approved',
                    'remarks'    => $log['mentor_remarks'] ?? '',
                    'attendance' => array_column($log['attendance'] ?? [], 'present', 'user_id')
                ]) : 'null';
            ?>
                <div class="flex justify-between items-center pt-3 border-t border-ui flex-wrap gap-3">
                    <button type="button"
                            onclick="openAttendanceModal(<?php echo htmlspecialchars(json_encode($meetingData), ENT_QUOTES, 'UTF-8'); ?>)"
                            class="btn-ui px-4 py-2 text-sm font-semibold hover-overlay-medium transition flex items-center gap-2 shadow-sm"
                            style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-user-check"></i>
                        <?php echo empty($log['attendance']) ? 'Mark Attendance' : 'Edit Attendance'; ?>
                    </button>

                    <button type="button"
                            onclick="openMentorReviewModal(<?php echo $wk; ?>, <?php echo $log ? $log['id'] : 'null'; ?>, this.getAttribute('data-log'))"
                            data-log="<?php echo e($logJson); ?>"
                            class="btn-ui px-4 py-2 text-sm font-semibold hover-overlay-medium transition flex items-center gap-2"
                            style="color: var(--accent-2); border-color: var(--accent-2);">
                        <i class="fas fa-clipboard-check"></i> Guide Feedback &amp; Action Items
                    </button>
                </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

        <script>
            function switchLogWeek(weekNum) {
                // Hide all week panes
                document.querySelectorAll('.week-log-pane').forEach(function(pane) {
                    pane.classList.add('hidden');
                });
                // Show target week pane
                const targetPane = document.getElementById('week-pane-' + weekNum);
                if (targetPane) {
                    targetPane.classList.remove('hidden');
                }
                // Update nav button styling
                document.querySelectorAll('.week-nav-btn').forEach(function(btn) {
                    btn.classList.remove('border-accent', 'bg-overlay-medium', 'text-white', 'ring-1', 'ring-accent');
                    btn.classList.add('border-ui', 'bg-raised', 'text-muted-ui');
                    btn.style.borderColor = '';
                });
                const activeBtn = document.getElementById('week-nav-btn-' + weekNum);
                if (activeBtn) {
                    activeBtn.classList.remove('border-ui', 'bg-raised', 'text-muted-ui');
                    activeBtn.classList.add('border-accent', 'bg-overlay-medium', 'text-white', 'ring-1', 'ring-accent');
                    activeBtn.style.borderColor = 'var(--accent)';
                }
            }
        </script>

    <?php endif; ?>
</div>