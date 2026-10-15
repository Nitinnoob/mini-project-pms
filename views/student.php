<!-- ============================================================ -->
<!-- SHARED PROJECT VIEW — Students & Teacher Drilldown           -->
<!-- ============================================================ -->
<div class="col-span-4 flex flex-col h-full gap-4">

    <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
    <div class="flex items-center justify-between bg-overlay-subtle border border-ui rounded px-4 py-2.5 mb-2 flex-wrap gap-2">
        <div class="flex items-center gap-2.5">
            <span class="badge badge-accent-2 text-[10px] font-mono-ui uppercase tracking-wider">Teacher Mode</span>
            <span class="font-semibold text-sm">Review: <?php echo e($viewData['myProject']['name'] ?? ''); ?></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openMeetingModal()" class="btn-ui px-3.5 py-1.5 text-xs font-semibold flex items-center gap-1.5" style="background: var(--accent); color: var(--bg);">
                <i class="fas fa-plus"></i> Add Meeting
            </button>
            <?php if (!empty($viewData['canEvaluateProject'])): ?>
            <button type="button" onclick="document.getElementById('marksModal').classList.remove('hidden')" class="btn-ui px-3.5 py-1.5 text-xs font-semibold flex items-center gap-1.5" style="color: var(--accent); border-color: var(--accent);">
                <i class="fas fa-clipboard-check"></i> Evaluate Marks
            </button>
            <?php endif; ?>
            <a href="dashboard.php?classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5" style="color: var(--accent-2); border-color: var(--accent-2);">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attendance Stats Bar -->
    <?php require __DIR__ . '/partials/attendance_stats.php'; ?>

    <!-- CIE Card (teacher drilldown only) -->
    <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
        <?php require __DIR__ . '/partials/cie_card.php'; ?>
    <?php endif; ?>

    <!-- Project Tabs -->
    <div class="flex justify-between items-center border-b border-ui">
        <div class="flex gap-6">
            <button id="btn-tab-team" class="tab-btn active pb-2" onclick="switchProjectTab('team')">Team</button>
            <button id="btn-tab-meetings" class="tab-btn pb-2" onclick="switchProjectTab('meetings')">Meetings</button>
        </div>
    </div>

    <!-- TAB 1: Team (roster / GitHub / requests / invites) -->
    <div id="project-tab-team" class="view-pane flex-1 flex flex-col gap-8">

        <!-- GitHub Repository Link -->
        <?php
        $ghUrl = $viewData['myProject']['github_url'] ?? '';
        $isLeader = !empty($viewData['isLeaderView']) && empty($viewData['isTeacherDrilldown']);
        ?>
        <div>
            <h3 class="font-head font-semibold mb-3 flex items-center gap-2">
                <i class="fab fa-github text-lg"></i> GitHub Repository
            </h3>
            <?php if ($isLeader): ?>
            <form id="github-url-form" method="POST" action="update_github_url.php" class="flex items-center gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                <input type="text" name="github_url" id="github-url-input"
                       value="<?php echo e($ghUrl); ?>"
                       placeholder="https://github.com/user/repo"
                       class="flex-1 px-3 py-2 text-sm bg-panel border border-ui rounded font-mono-ui focus:outline-none focus:border-accent transition">
                <button type="submit" class="btn-ui px-3 py-2 text-xs font-semibold bg-accent text-bg hover:opacity-90 flex items-center gap-1.5">
                    <i class="fas fa-save"></i> Save
                </button>
            </form>
            <?php else: ?>
                <?php if (!empty($ghUrl)): ?>
                <a href="<?php echo e($ghUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-mono-ui px-3 py-2 bg-raised border border-ui rounded hover:border-accent transition">
                    <i class="fas fa-external-link-alt text-xs text-accent"></i>
                    <?php echo e($ghUrl); ?>
                </a>
                <?php else: ?>
                <div class="text-sm text-muted-ui p-3 border border-ui border-dashed rounded text-center">
                    No repository linked yet.
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Pending Join Requests (Leader Only) -->
        <?php if (!empty($viewData['isLeaderView']) && empty($viewData['isTeacherDrilldown'])): ?>
        <div>
            <h3 class="font-head font-semibold mb-4 flex justify-between items-center">
                Pending Requests
                <?php if (count($viewData['pendingRequests'] ?? []) > 0): ?>
                    <span class="badge badge-danger"><?php echo count($viewData['pendingRequests']); ?> new</span>
                <?php endif; ?>
            </h3>

            <?php if (empty($viewData['pendingRequests'])): ?>
                <div class="text-sm text-muted-ui p-4 border border-ui border-dashed rounded text-center">
                    No pending requests. Invite classmates from the Roster!
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($viewData['pendingRequests'] as $req): ?>
                    <div class="flex items-center justify-between p-3 bg-raised border border-ui rounded">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-accent-2 text-bg">
                                <?php echo strtoupper(substr($req['username'] ?? '', 0, 1)); ?>
                            </div>
                            <span class="font-semibold text-sm"><?php echo e($req['username'] ?? ''); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="manage_join_request.php" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo (int)$req['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold bg-accent text-bg hover:opacity-90">Accept</button>
                            </form>
                            <form method="POST" action="manage_join_request.php" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                <input type="hidden" name="project_id" value="<?php echo (int)$viewData['myProjectId']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo (int)$req['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold border border-ui hover-overlay-medium transition" style="color: var(--danger); border-color: var(--danger);">Decline</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Active Team Roster -->
        <div>
            <h3 class="font-head font-semibold mb-4">Active Team Roster</h3>
            <div class="space-y-3">
                <?php foreach ($viewData['teamRoster'] ?? [] as $m): ?>
                <div class="flex items-center justify-between p-3.5 bg-raised border border-ui rounded-lg flex-wrap gap-3">
                    <div class="flex items-center gap-3.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent); color: var(--bg);">
                            <?php echo strtoupper(substr($m['name'] ?? '', 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-semibold text-sm"><?php echo e($m['name'] ?? ''); ?></div>
                            <div class="text-xs text-muted-ui font-mono-ui"><?php echo e($m['role'] ?? 'Member'); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-mono-ui">
                        <div>
                            <span class="text-muted-ui block text-[10px] uppercase">Attendance</span>
                            <span class="font-semibold <?php echo ($m['attendance_pct'] !== null && $m['attendance_pct'] < 75.0) ? 'text-status-amber' : 'text-text'; ?>">
                                <?php echo $m['attendance_pct'] !== null ? ($m['attendance_pct'] . '%') : '-'; ?>
                            </span>
                        </div>
                        <?php if ($m['attendance_pct'] !== null && $m['attendance_pct'] < 75.0): ?>
                        <span class="badge badge-danger text-[10px] font-mono-ui py-0.5 px-1.5">Shortage</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Team Building / Invite Classmates (Leader Only) -->
        <?php if (!empty($viewData['isLeaderView']) && empty($viewData['isTeacherDrilldown'])): ?>
        <div>
            <h3 class="font-head font-semibold mb-4">Team Building</h3>
            <div class="p-6 border border-ui border-dashed rounded text-center">
                <p class="text-sm text-muted-ui mb-4">You have <?php echo max(0, ($viewData['maxTeamSize'] ?? 4) - count($viewData['actualTeamRoster'] ?? [])); ?> open slots remaining on your team (Max <?php echo (int)($viewData['maxTeamSize'] ?? 4); ?>).</p>
                <button type="button" onclick="document.getElementById('inviteModal').classList.add('show')" class="btn-ui px-4 py-2 text-sm font-semibold bg-accent-2 text-bg hover:opacity-90">
                    <i class="fas fa-user-plus mr-2"></i> Browse Classmates
                </button>
            </div>
        </div>

        <!-- Invite Modal -->
        <div id="inviteModal" class="success-overlay">
            <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-lg shadow-xl pointer-events-auto max-h-[80vh] flex flex-col">
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui">
                    <h2 class="text-xl font-bold font-head">Invite to Project</h2>
                    <button type="button" onclick="document.getElementById('inviteModal').classList.remove('show')" class="text-muted-ui hover:text-accent-2 transition"><i class="fas fa-times"></i></button>
                </div>
                <div class="overflow-y-auto flex-1 space-y-3 pr-2">
                    <?php
                    $classmatesList = $viewData['unassignedClassmates'] ?? [];
                    if (empty($classmatesList)): ?>
                        <div class="text-sm text-muted-ui text-center p-4">No unassigned classmates left!</div>
                    <?php else:
                        foreach($classmatesList as $c): ?>
                    <div class="flex items-center justify-between p-3 bg-raised border border-ui rounded">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs bg-overlay-strong text-muted-ui border border-ui">
                                <?php echo strtoupper(substr($c['username'] ?? '', 0, 1)); ?>
                            </div>
                            <div>
                                <span class="block font-semibold text-sm"><?php echo e($c['username'] ?? ''); ?></span>
                                <?php if (!empty($c['usn'])): ?>
                                <span class="block text-xs text-muted-ui font-mono-ui"><?php echo e($c['usn']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="px-4 py-1.5 text-xs font-semibold rounded bg-overlay-strong text-muted-ui border border-ui hover:border-accent hover:text-accent transition invite-btn" data-user-id="<?php echo (int)$c['id']; ?>" data-project-id="<?php echo (int)$viewData['myProjectId']; ?>" data-classroom-id="<?php echo e($viewData['classroom_id']); ?>">
                            Invite
                        </button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB 2: Meetings -->
    <div id="project-tab-meetings" class="view-pane flex-1 flex flex-col hidden">
        <?php
        $meetings = $viewData['meetings'] ?? [];
        $currentUserId = $viewData['currentUserId'] ?? ($_SESSION['user_id'] ?? 0);
        ?>
        <?php if (empty($meetings)): ?>
            <div class="text-sm text-muted-ui p-8 border border-ui border-dashed rounded text-center">
                <i class="fas fa-calendar-alt text-2xl mb-3 block opacity-50"></i>
                No meetings recorded yet.
                <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
                    <br><span class="text-xs">Click <strong>Add Meeting</strong> above to record one.</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($meetings as $idx => $mtg):
                    $meetingNum = $idx + 1;
                    $meetingDate = $mtg['meeting_date'] ?? '';
                    $guidanceNotes = $mtg['guidance_notes'] ?? '';

                    // Find current user's attendance status
                    $myStatus = null;
                    $attendanceMap = [];
                    foreach ($mtg['attendance'] ?? [] as $att) {
                        $attendanceMap[(int)$att['user_id']] = $att;
                        if ((int)$att['user_id'] === (int)$currentUserId) {
                            $myStatus = $att['status'] ?? null;
                        }
                    }
                ?>
                <div class="bg-raised border border-ui rounded-lg p-4">
                    <!-- Meeting Header -->
                    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                        <div class="flex items-center gap-3">
                            <span class="badge badge-accent text-[10px] font-mono-ui py-0.5 px-2">#<?php echo $meetingNum; ?></span>
                            <span class="text-sm font-semibold">
                                <?php echo e($meetingDate ? date('D, d M Y', strtotime($meetingDate)) : ''); ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($myStatus === 'present'): ?>
                                <span class="badge badge-green text-[10px] font-mono-ui py-0.5 px-2 flex items-center gap-1">
                                    <i class="fas fa-check"></i> Present
                                </span>
                            <?php elseif ($myStatus === 'absent'): ?>
                                <span class="badge badge-danger text-[10px] font-mono-ui py-0.5 px-2 flex items-center gap-1">
                                    <i class="fas fa-times"></i> Absent
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
                                <button type="button" onclick="openMeetingModal(<?php echo (int)$mtg['id']; ?>, '<?php echo e($meetingDate); ?>', <?php echo htmlspecialchars(json_encode($guidanceNotes), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($attendanceMap, JSON_FORCE_OBJECT), ENT_QUOTES, 'UTF-8'); ?>)" class="btn-ui px-2.5 py-1 text-[11px] font-semibold flex items-center gap-1 hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Guidance Notes -->
                    <?php if (!empty($guidanceNotes)): ?>
                    <div class="text-sm text-text leading-relaxed bg-panel border border-ui rounded p-3">
                        <div class="text-[10px] text-muted-ui font-semibold uppercase tracking-wider mb-1.5">Guidance Notes</div>
                        <?php echo nl2br(e($guidanceNotes)); ?>
                    </div>
                    <?php else: ?>
                    <div class="text-xs text-muted-ui italic">No guidance notes recorded.</div>
                    <?php endif; ?>

                    <!-- Full attendance roster (teacher drilldown only) -->
                    <?php if (!empty($viewData['isTeacherDrilldown']) && !empty($mtg['attendance'])): ?>
                    <div class="mt-3 pt-3 border-t border-ui">
                        <div class="text-[10px] text-muted-ui font-semibold uppercase tracking-wider mb-2">Attendance</div>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($mtg['attendance'] as $att): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-mono-ui px-2 py-0.5 rounded border border-ui <?php echo ($att['status'] === 'present') ? 'bg-green-500/10 text-status-green' : 'bg-red-500/10 text-status-red'; ?>">
                                <i class="fas <?php echo ($att['status'] === 'present') ? 'fa-check' : 'fa-times'; ?> text-[9px]"></i>
                                <?php echo e($att['username'] ?? ''); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function switchProjectTab(tab) {
    const tabs = ['team', 'meetings'];
    tabs.forEach(function(t) {
        const btn  = document.getElementById('btn-tab-' + t);
        const pane = document.getElementById('project-tab-' + t);
        if (btn)  btn.classList.toggle('active', t === tab);
        if (pane) pane.classList.toggle('hidden', t !== tab);
    });
}

// Backward-compatible alias
function switchLeaderTab(tab) {
    switchProjectTab(tab);
}

document.addEventListener('DOMContentLoaded', function() {
    // Invite button handlers
    var inviteButtons = document.querySelectorAll('.invite-btn');
    inviteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var btn = this;
            var userId      = btn.getAttribute('data-user-id');
            var projectId   = btn.getAttribute('data-project-id');
            var classroomId = btn.getAttribute('data-classroom-id');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            var headers   = { 'Content-Type': 'application/x-www-form-urlencoded' };
            if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

            var params = {
                'classroom_id': classroomId,
                'project_id':   projectId,
                'user_id':      userId,
                'action':       'invite'
            };
            if (csrfToken) params.csrf_token = csrfToken;

            fetch('handle_invitation.php', {
                method:  'POST',
                headers: headers,
                body:    new URLSearchParams(params)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Invited';
                    btn.classList.add('text-accent', 'border-accent');
                    btn.classList.remove('text-muted-ui', 'border-ui', 'bg-overlay-strong');
                    btn.style.backgroundColor = 'var(--accent-2)';
                    btn.style.borderColor     = 'var(--accent-2)';
                    btn.style.color           = 'var(--bg)';
                } else {
                    btn.innerHTML = '<i class="fas fa-times"></i> Error';
                    btn.classList.add('text-danger', 'border-danger');
                    setTimeout(function() {
                        btn.innerHTML = 'Invite';
                        btn.classList.remove('text-danger', 'border-danger');
                        btn.disabled = false;
                    }, 2000);
                }
            })
            .catch(function() {
                btn.innerHTML = '<i class="fas fa-times"></i> Error';
                btn.classList.add('text-danger', 'border-danger');
                setTimeout(function() {
                    btn.innerHTML = 'Invite';
                    btn.classList.remove('text-danger', 'border-danger');
                    btn.disabled = false;
                }, 2000);
            });
        });
    });
});
</script>