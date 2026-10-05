<?php
/**
 * Shared Weekly Logs / Phases view (used by Student & Project Leader).
 * All data arrives pre-built in $viewData — this partial never queries.
 *
 * Transparency rule: every active member sees the same log bodies, files,
 * attendance and mentor remarks. Only the submission form is leader-only.
 */
$canSubmit = !empty($viewData['isLeaderView']) && empty($viewData['isTeacherDrilldown']);
$isMentor  = !empty($viewData['isTeacherDrilldown'])
             && (!empty($viewData['isCoordinator']) || (($viewData['myProject']['mentor_id'] ?? 0) == $_SESSION['user_id']));
?>

<div class="card p-5 flex-1 overflow-y-auto flex flex-col">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <h3 class="font-head font-semibold text-lg">Phases &amp; Weekly Logs</h3>
        <?php if (!$canSubmit && !$isMentor && !$viewData['hasSchedule']): ?>
            <span class="text-xs text-muted-ui">Logs are disabled until the coordinator sets start and end dates.</span>
        <?php elseif (!$canSubmit && !$isMentor): ?>
            <span class="text-xs text-muted-ui flex items-center gap-1">
                <i class="fas fa-eye"></i> Read-only — your leader submits these
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
                     title="<?php echo htmlspecialchars($p['date_from'] . ' → ' . $p['date_to']); ?>">
                    <div class="font-semibold font-head truncate"><?php echo htmlspecialchars($p['label']); ?></div>
                    <div class="text-muted-ui font-mono-ui mt-0.5">
                        <?php echo htmlspecialchars(date('M j', strtotime($p['date_from']))); ?>
                    </div>
                    <?php if ($p['is_current']): ?>
                        <div class="mt-1 font-semibold" style="color: var(--accent-2);">Current</div>
                    <?php elseif ($p['is_merged']): ?>
                        <div class="mt-1 text-muted-ui">Merged</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Weekly submissions -->
        <?php foreach ($viewData['logWeeks'] as $p):
            $wk  = $p['week_number'];
            $log = $viewData['weeklyLogs'][$wk] ?? null;
            $status = $log ? ($log['review_status'] ?? 'pending') : 'unsubmitted';

            $statusBadge = '';
            if ($status === 'unsubmitted')   $statusBadge = '<span class="badge badge-muted">Not Submitted</span>';
            elseif ($status === 'pending')   $statusBadge = '<span class="badge badge-accent">Pending Review</span>';
            elseif ($status === 'approved')  $statusBadge = '<span class="badge badge-green">Approved</span>';
            elseif ($status === 'revision_needed') $statusBadge = '<span class="badge badge-danger">Revision Flagged</span>';

            $wkStat = $viewData['weekStats'][$wk] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
        ?>
        <div class="border border-ui rounded p-4 mb-4 bg-raised">
            <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <h4 class="font-semibold"><?php echo htmlspecialchars($p['label']); ?></h4>
                    <span class="text-xs text-muted-ui font-mono-ui">
                        <?php echo htmlspecialchars(date('M j', strtotime($p['date_from'])) . ' – ' . date('M j', strtotime($p['date_to']))); ?>
                    </span>
                    <?php if ($p['is_current']): ?>
                        <span class="badge" style="background: var(--accent-2); color: var(--bg);">Current</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($wkStat['total'] > 0): ?>
                        <span class="text-xs font-mono-ui text-muted-ui">
                            <?php echo $wkStat['done']; ?>/<?php echo $wkStat['total']; ?> tasks · <?php echo $wkStat['percent']; ?>%
                        </span>
                    <?php endif; ?>
                    <?php echo $statusBadge; ?>
                </div>
            </div>

            <?php if ($p['merged_from']): ?>
                <p class="text-xs text-muted-ui mb-3">
                    <i class="fas fa-code-branch mr-1"></i>
                    This phase also covers <?php echo htmlspecialchars(implode(', ', $p['merged_from'])); ?>.
                </p>
            <?php endif; ?>

            <?php if (!$log): ?>
                <p class="text-sm text-muted-ui mb-3">No submission for this phase.</p>
            <?php else: ?>
                <p class="text-sm font-semibold mt-2">Work Summary:</p>
                <p class="text-sm text-muted-ui mb-2"><?php echo nl2br(htmlspecialchars($log['work_summary'] ?? '')); ?></p>

                <p class="text-sm font-semibold">Next Steps:</p>
                <p class="text-sm text-muted-ui mb-3"><?php echo nl2br(htmlspecialchars($log['next_steps'] ?? '')); ?></p>

                <?php if (!empty($log['files'])): ?>
                    <p class="text-sm font-semibold">Attachments:</p>
                    <div class="flex flex-wrap gap-2 mt-1 mb-3">
                        <?php foreach ($log['files'] as $f): ?>
                            <a href="<?php echo htmlspecialchars($f['file_path']); ?>" download
                               class="inline-flex items-center gap-2 px-3 py-1 text-xs rounded bg-overlay-subtle border border-ui hover:border-accent transition text-muted-ui hover:text-accent">
                                <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($f['file_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($log['mentor_remarks'])): ?>
                    <div class="mt-4 p-3 rounded text-sm bg-overlay-subtle border border-ui">
                        <p class="font-semibold mb-1"><i class="fas fa-comment-dots text-muted-ui me-2"></i>Mentor Remarks</p>
                        <p class="text-muted-ui"><?php echo nl2br(htmlspecialchars($log['mentor_remarks'] ?? '')); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($log['attendance'])): ?>
                    <div class="mt-4 p-3 rounded text-sm bg-overlay-subtle border border-ui">
                        <p class="font-semibold mb-2"><i class="fas fa-users text-muted-ui me-2"></i>Meeting Attendance</p>
                        <div class="flex flex-col gap-1">
                            <?php foreach ($log['attendance'] as $att): ?>
                                <div class="flex justify-between items-center text-xs">
                                    <span><?php echo htmlspecialchars($att['username']); ?></span>
                                    <?php if ($att['present']): ?>
                                        <span class="font-semibold" style="color: var(--status-green);"><i class="fas fa-check me-1"></i>Present</span>
                                    <?php else: ?>
                                        <span class="font-semibold" style="color: var(--danger);"><i class="fas fa-times me-1"></i>Absent</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($canSubmit && (!$log || $status === 'revision_needed')): ?>
                <div class="<?php echo $log ? 'mt-4 pt-3 border-t border-ui' : ''; ?>">
                    <button onclick="openWeeklyLogModal(<?php echo $wk; ?>)" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-upload me-2"></i> <?php echo $log ? 'Submit Revision' : 'Submit Weekly Log'; ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($isMentor):
                $logJson = $log ? json_encode([
                    'status'     => $log['review_status'] ?? 'pending',
                    'remarks'    => $log['mentor_remarks'] ?? '',
                    'attendance' => array_column($log['attendance'] ?? [], 'present', 'user_id')
                ]) : 'null';
            ?>
                <div class="mt-4 flex justify-end pt-3 border-t border-ui">
                    <button onclick="openMentorReviewModal(<?php echo $wk; ?>, <?php echo $log ? $log['id'] : 'null'; ?>, this.getAttribute('data-log'))"
                            data-log="<?php echo htmlspecialchars($logJson); ?>"
                            class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent-2); border-color: var(--accent-2);">
                        <i class="fas fa-clipboard-check me-2"></i> Mentor Review
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>