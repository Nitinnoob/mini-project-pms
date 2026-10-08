<?php
/**
 * Shared Weekly Logs / Phases view (used by Student & Project Leader).
 * All data arrives pre-built in $viewData — this partial never queries.
 *
 * Phase 4:
 * 1. Team writes weekly update (work done, next steps, blockers) and uploads files.
 *    Any active member can submit; captures submitted_by.
 * 2. Guide inputs feedback and adds specific action items into guide_instructions.
 * 3. Rollover: Prior open instructions displayed at top. Students acknowledge; guide marks done.
 * 4. Append-only guide feedback history across meetings.
 * 5. Secure file uploads: MIME inspection, size limits, generated filenames.
 */
$canSubmit = !empty($viewData['canSubmitWeeklyUpdate']) && empty($viewData['isTeacherDrilldown']);
$isMentor  = !empty($viewData['isTeacherDrilldown'])
             && (!empty($viewData['isCoordinator']) || (($viewData['myProject']['mentor_id'] ?? 0) == $_SESSION['user_id']));
$rolloverInstructions = $viewData['rolloverInstructions'] ?? [];
?>

<div class="card p-5 flex-1 overflow-y-auto flex flex-col">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <h3 class="font-head font-semibold text-lg">Saturday Guide Reviews &amp; Weekly Logs</h3>
        <?php if (!$canSubmit && !$isMentor && !$viewData['hasSchedule']): ?>
            <span class="text-xs text-muted-ui">Logs are disabled until the coordinator sets start and end dates.</span>
        <?php elseif ($canSubmit): ?>
            <span class="text-xs text-muted-ui flex items-center gap-1.5">
                <i class="fas fa-users text-accent"></i> Any active team member can submit weekly updates
            </span>
        <?php elseif (!$canSubmit && !$isMentor): ?>
            <span class="text-xs text-muted-ui flex items-center gap-1">
                <i class="fas fa-eye"></i> Read-only view
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$viewData['hasSchedule']): ?>
        <div class="py-12 text-center text-muted-ui text-sm italic border border-ui rounded bg-raised">
            The classroom coordinator has not set start and end dates. Weekly logs are disabled.
        </div>
    <?php else: ?>

        <!-- Phase strip: derived from classroom start/end dates -->
        <div class="flex gap-2 mb-5 overflow-x-auto pb-2">
            <?php foreach ($viewData['phases'] as $p): ?>
                <div class="flex-none text-center px-3 py-2 rounded border text-xs min-w-[76px]
                            <?php echo $p['week_state'] === 'current' ? 'border-accent' : 'border-ui'; ?>
                            <?php echo $p['is_merged'] ? 'opacity-50' : ''; ?>"
                     <?php echo $p['week_state'] === 'current' ? 'style="border-color: var(--accent-2);"' : ''; ?>
                     title="<?php echo e($p['date_from'] . ' → ' . $p['date_to']); ?>">
                    <div class="font-semibold font-head truncate"><?php echo e($p['label']); ?></div>
                    <div class="text-muted-ui font-mono-ui mt-0.5">
                        <?php echo e(date('M j', strtotime($p['date_from']))); ?>
                    </div>
                    <?php if ($p['is_current']): ?>
                        <div class="mt-1 font-semibold" style="color: var(--accent-2);">Current</div>
                    <?php elseif ($p['is_merged']): ?>
                        <div class="mt-1 text-muted-ui">Merged</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 3. Rollover: Prior open instructions displayed at the top -->
        <?php if (!empty($rolloverInstructions)): ?>
        <div class="mb-5 p-4 rounded border border-amber-500/30 bg-amber-950/20">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs font-bold">
                        <i class="fas fa-tasks"></i>
                    </span>
                    <h4 class="font-head font-semibold text-sm text-amber-200">
                        Action Items Rollover (Prior Mentor Instructions)
                    </h4>
                    <span class="badge badge-accent text-[11px]"><?php echo count($rolloverInstructions); ?> open</span>
                </div>
                <span class="text-[11px] text-muted-ui">
                    Instructions persist across weeks until marked done by your guide.
                </span>
            </div>

            <div class="space-y-2">
                <?php foreach ($rolloverInstructions as $inst):
                    $isAck = ($inst['status'] === 'acknowledged');
                ?>
                <div class="flex items-center justify-between p-3 rounded bg-raised border border-ui text-xs flex-wrap gap-2 shadow-sm">
                    <div class="flex-1 min-w-[240px]">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="font-mono-ui font-semibold text-[11px] px-1.5 py-0.5 rounded bg-overlay-medium text-muted-ui">
                                Week <?php echo (int)$inst['week_number']; ?>
                            </span>
                            <?php if ($isAck): ?>
                                <span class="badge badge-accent text-[10px]"><i class="fas fa-check mr-1"></i>Acknowledged</span>
                            <?php else: ?>
                                <span class="badge badge-danger text-[10px]"><i class="fas fa-exclamation-circle mr-1"></i>Action Required</span>
                            <?php endif; ?>
                            <span class="text-muted-ui text-[11px]">from <?php echo e($inst['created_by_name']); ?></span>
                        </div>
                        <div class="font-medium text-sm text-text"><?php echo nl2br(e($inst['text'])); ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($canSubmit && !$isAck): ?>
                            <form method="POST" action="manage_instruction.php" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="acknowledge">
                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                <button type="submit" class="btn-ui px-3 py-1 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1 text-accent border border-accent">
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
                                <button type="submit" class="btn-ui px-3 py-1 text-xs font-semibold transition flex items-center gap-1 shadow-sm" style="background: var(--accent-2); color: var(--bg);">
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

        <!-- Weekly meetings / submissions -->
        <?php foreach ($viewData['logWeeks'] as $p):
            $wk  = $p['week_number'];
            $log = $viewData['weeklyLogs'][$wk] ?? null;
            $mStatus = $log['meeting_status'] ?? ($log['status'] ?? 'scheduled');
            $reviewStatus = $log ? ($log['review_status'] ?? 'pending') : 'unsubmitted';

            $meetingBadge = '';
            if ($mStatus === 'held') {
                $meetingBadge = '<span class="badge badge-green"><i class="fas fa-check-circle me-1"></i>Meeting Held</span>';
            } elseif ($mStatus === 'rescheduled') {
                $meetingBadge = '<span class="badge badge-accent"><i class="fas fa-redo-alt me-1"></i>Rescheduled</span>';
            } elseif ($mStatus === 'holiday') {
                $meetingBadge = '<span class="badge badge-muted"><i class="fas fa-umbrella-beach me-1"></i>Holiday</span>';
            } else {
                $meetingBadge = '<span class="badge badge-muted"><i class="fas fa-calendar-alt me-1"></i>Scheduled</span>';
            }

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
        <div class="border border-ui rounded p-4 mb-4 bg-raised">
            <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-base"><?php echo e($p['label']); ?></h4>
                    <span class="text-xs text-muted-ui font-mono-ui">
                        <?php echo e(date('M j', strtotime($p['date_from'])) . ' – ' . date('M j', strtotime($p['date_to']))); ?>
                    </span>
                    <?php if ($p['is_current']): ?>
                        <span class="badge" style="background: var(--accent-2); color: var(--bg);">Current Week</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php echo $meetingBadge; ?>
                </div>
            </div>

            <?php if ($p['merged_from']): ?>
                <p class="text-xs text-muted-ui mb-3">
                    <i class="fas fa-code-branch mr-1"></i>
                    This phase also covers <?php echo e(implode(', ', $p['merged_from'])); ?>.
                </p>
            <?php endif; ?>

            <?php if (!$hasUpdate && empty($log['attendance']) && empty($log['mentor_remarks']) && empty($instructions)): ?>
                <p class="text-sm text-muted-ui mb-3">No team update or review recorded for this Saturday yet.</p>
            <?php else: ?>
                <!-- Submitter attribution -->
                <?php if (!empty($log['submitted_by_name']) || !empty($log['submitted_at'])): ?>
                    <div class="text-[11px] text-muted-ui mb-2 flex items-center gap-1.5 font-mono-ui">
                        <i class="fas fa-user-edit text-accent"></i>
                        <span>Submitted by <strong><?php echo e($log['submitted_by_name'] ?? 'Team Member'); ?></strong></span>
                        <?php if (!empty($log['submitted_at'])): ?>
                            <span>&middot; <?php echo e(date('M j, Y g:i A', strtotime($log['submitted_at']))); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Work Done -->
                <?php if (!empty($log['work_summary'])): ?>
                    <div class="mt-2 mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-ui block mb-0.5">
                            <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Work Accomplished:
                        </span>
                        <div class="text-sm text-text"><?php echo nl2br(e($log['work_summary'])); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Next Steps -->
                <?php if (!empty($log['next_steps'])): ?>
                    <div class="mt-2 mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-ui block mb-0.5">
                            <i class="fas fa-arrow-circle-right text-accent mr-1"></i> Next Steps:
                        </span>
                        <div class="text-sm text-text"><?php echo nl2br(e($log['next_steps'])); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Blockers & Impediments -->
                <?php if (!empty($log['blockers'])): ?>
                    <div class="mt-2 mb-2 p-2.5 rounded bg-amber-950/20 border border-amber-500/30 text-xs">
                        <span class="font-semibold text-amber-400 block mb-0.5">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Blockers &amp; Impediments:
                        </span>
                        <div class="text-text"><?php echo nl2br(e($log['blockers'])); ?></div>
                    </div>
                <?php endif; ?>

                <!-- File Attachments (Secure Uploads) -->
                <?php if (!empty($log['files'])): ?>
                    <div class="mt-3 mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-ui block mb-1">
                            <i class="fas fa-paperclip text-muted-ui mr-1"></i> Attached Artifacts:
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($log['files'] as $f):
                                $fName = $f['original_name'] ?? ($f['file_name'] ?? 'Attachment');
                                $fSizeStr = !empty($f['file_size']) ? ' (' . round($f['file_size'] / 1024) . ' KB)' : '';
                            ?>
                                <a href="<?php echo e($f['file_path']); ?>" download
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded bg-overlay-subtle border border-ui hover:border-accent transition text-muted-ui hover:text-accent font-mono-ui">
                                    <i class="fas fa-file-download text-accent"></i>
                                    <span><?php echo e($fName) . $fSizeStr; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. Meeting Action Items / Instructions -->
                <?php if (!empty($instructions)): ?>
                    <div class="mt-3 p-3 rounded bg-overlay-subtle border border-ui">
                        <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                            <span class="font-semibold text-xs flex items-center gap-1.5">
                                <i class="fas fa-tasks text-accent"></i> Meeting Action Items &amp; Instructions
                            </span>
                            <span class="text-[11px] text-muted-ui font-mono-ui"><?php echo count($instructions); ?> item(s)</span>
                        </div>
                        <div class="space-y-1.5">
                            <?php foreach ($instructions as $inst):
                                $iStatus = $inst['status'];
                            ?>
                                <div class="flex items-center justify-between p-2 rounded bg-raised border border-ui text-xs flex-wrap gap-2">
                                    <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                                        <?php if ($iStatus === 'done'): ?>
                                            <span class="badge badge-green text-[10px]"><i class="fas fa-check-double mr-1"></i>Done</span>
                                        <?php elseif ($iStatus === 'acknowledged'): ?>
                                            <span class="badge badge-accent text-[10px]"><i class="fas fa-check mr-1"></i>Acknowledged</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger text-[10px]"><i class="fas fa-exclamation-circle mr-1"></i>Open</span>
                                        <?php endif; ?>
                                        <span class="text-text font-medium"><?php echo e($inst['text']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <?php if ($canSubmit && $iStatus === 'open'): ?>
                                            <form method="POST" action="manage_instruction.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="acknowledge">
                                                <input type="hidden" name="instruction_id" value="<?php echo (int)$inst['id']; ?>">
                                                <input type="hidden" name="classroom_id" value="<?php echo (int)$viewData['classroom_id']; ?>">
                                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                                <button type="submit" class="btn-ui px-2 py-0.5 text-[11px] font-semibold text-accent border border-accent hover-overlay-medium">
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
                                                <button type="submit" class="btn-ui px-2 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/40 hover-overlay-medium">
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

                <!-- 4. Append-Only Guide Feedback History Timeline -->
                <?php if (!empty($feedbackHistory)): ?>
                    <div class="mt-3 p-3 rounded bg-overlay-subtle border border-ui">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-xs flex items-center gap-1.5">
                                <i class="fas fa-comment-dots text-accent"></i> Guide Feedback History
                            </span>
                            <span class="text-[11px] text-muted-ui font-mono-ui"><?php echo count($feedbackHistory); ?> review(s)</span>
                        </div>
                        <div class="space-y-2">
                            <?php foreach ($feedbackHistory as $fb):
                                $fbStatus = $fb['status'] ?? 'held';
                                $fbBadge = match ($fbStatus) {
                                    'approved'        => '<span class="badge badge-green text-[10px]">Approved</span>',
                                    'revision_needed' => '<span class="badge badge-danger text-[10px]">Revision Flagged</span>',
                                    default           => '<span class="badge badge-accent text-[10px]">Feedback</span>',
                                };
                            ?>
                                <div class="p-2.5 rounded bg-raised border border-ui text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-2">
                                            <strong class="text-text"><?php echo e($fb['reviewer_name'] ?? 'Guide'); ?></strong>
                                            <?php echo $fbBadge; ?>
                                        </div>
                                        <span class="text-[10px] text-muted-ui font-mono-ui">
                                            <?php echo e(date('M j, Y g:i A', strtotime($fb['created_at'] ?? 'now'))); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted-ui"><?php echo nl2br(e($fb['feedback'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif (!empty($log['mentor_remarks'])): ?>
                    <div class="mt-3 p-3 rounded bg-overlay-subtle border border-ui">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-xs flex items-center gap-1.5">
                                <i class="fas fa-comment-dots text-muted-ui"></i> Guide Feedback
                            </span>
                        </div>
                        <p class="text-xs text-muted-ui"><?php echo nl2br(e($log['mentor_remarks'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Meeting Attendance -->
                <?php if (!empty($log['attendance'])): ?>
                    <div class="mt-3 p-3 rounded text-sm bg-overlay-subtle border border-ui">
                        <div class="flex justify-between items-center mb-2 flex-wrap gap-1">
                            <span class="font-semibold text-xs flex items-center gap-1.5">
                                <i class="fas fa-users text-muted-ui"></i> Saturday Guide Meeting Attendance
                            </span>
                            <?php if ($mStatus === 'holiday' || $mStatus === 'rescheduled'): ?>
                                <span class="text-[11px] text-muted-ui italic">(No absences counted for <?php echo e(ucfirst($mStatus)); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php foreach ($log['attendance'] as $att):
                                $attStatus = $att['status'] ?? ($att['present'] ? 'present' : 'absent');
                            ?>
                                <div class="flex justify-between items-center text-xs p-2 rounded bg-raised border border-ui">
                                    <span class="font-medium truncate mr-2"><?php echo e($att['username'] ?? 'Student'); ?></span>
                                    <?php if ($attStatus === 'present'): ?>
                                        <span class="font-semibold flex-none flex items-center gap-1 text-emerald-400">
                                            <i class="fas fa-check"></i> Present
                                        </span>
                                    <?php elseif ($attStatus === 'excused'): ?>
                                        <span class="font-semibold flex-none flex items-center gap-1 text-amber-400">
                                            <i class="fas fa-shield-alt"></i> Excused
                                        </span>
                                    <?php else: ?>
                                        <span class="font-semibold flex-none flex items-center gap-1 text-rose-400">
                                            <i class="fas fa-times"></i> Absent
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($log['attendance_changes'])): ?>
                            <div class="mt-2.5 pt-2 border-t border-ui text-[11px] text-muted-ui">
                                <span class="font-semibold text-accent"><i class="fas fa-history mr-1"></i>Attendance Audit Trail:</span>
                                <div class="space-y-1 mt-1">
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

            <!-- 1. Submit Weekly Update Button (Available to ANY active team member) -->
            <?php if ($canSubmit && (!$log || $reviewStatus === 'revision_needed' || empty($log['work_summary']))):
                $prefillJson = json_encode([
                    'work_done'  => $log['work_summary'] ?? '',
                    'next_steps' => $log['next_steps'] ?? '',
                    'blockers'   => $log['blockers'] ?? '',
                ]);
            ?>
                <div class="<?php echo $hasUpdate ? 'mt-4 pt-3 border-t border-ui' : ''; ?>">
                    <button type="button"
                            onclick="openWeeklyLogModal(<?php echo $wk; ?>, <?php echo htmlspecialchars($prefillJson, ENT_QUOTES, 'UTF-8'); ?>)"
                            class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5"
                            style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-upload"></i>
                        <?php echo ($hasUpdate && !empty($log['work_summary'])) ? 'Submit Revision' : 'Submit Weekly Meeting Update'; ?>
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
                <div class="mt-4 flex justify-between items-center pt-3 border-t border-ui flex-wrap gap-2">
                    <button type="button"
                            onclick="openAttendanceModal(<?php echo htmlspecialchars(json_encode($meetingData), ENT_QUOTES, 'UTF-8'); ?>)"
                            class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5 shadow-sm"
                            style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-user-check"></i>
                        <?php echo empty($log['attendance']) ? 'Mark Attendance' : 'Edit Attendance'; ?>
                    </button>

                    <button type="button"
                            onclick="openMentorReviewModal(<?php echo $wk; ?>, <?php echo $log ? $log['id'] : 'null'; ?>, this.getAttribute('data-log'))"
                            data-log="<?php echo e($logJson); ?>"
                            class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5"
                            style="color: var(--accent-2); border-color: var(--accent-2);">
                        <i class="fas fa-clipboard-check"></i> Guide Feedback &amp; Action Items
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>