<!-- ============================================================ -->
        <!-- PROJECT LEADER — Control room: stats, ticker, roster          -->
        <!-- ============================================================ -->
        <div class="col-span-4 flex flex-col h-full gap-4">

            <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
            <div class="flex items-center justify-between bg-overlay-subtle border border-ui rounded px-4 py-3 mb-2 flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 text-xs font-bold rounded uppercase tracking-wider" style="background: var(--accent-2); color: var(--bg);">Teacher Mode</span>
                    <span class="font-semibold text-sm">Audit & Review: <?php echo e($viewData['myProject']['name']); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (!empty($viewData['canEvaluateProject'])): ?>
                    <button type="button" onclick="document.getElementById('marksModal').classList.remove('hidden')" class="btn-ui px-3.5 py-1.5 text-xs font-semibold flex items-center gap-1.5" style="background: var(--accent); color: var(--bg);">
                        <i class="fas fa-clipboard-check"></i> Evaluate Marks
                    </button>
                    <?php endif; ?>
                    <a href="dashboard.php?classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent-2); border-color: var(--accent-2);">
                        <i class="fas fa-arrow-left"></i> Back to Ledger
                    </a>
                </div>
            </div>
            <?php endif; ?>


            <?php
            $pa = $viewData['personalAttendance'] ?? [
                'percentage'       => 100.0,
                'present'          => 0,
                'absent'           => 0,
                'excused'          => 0,
                'holiday'          => 0,
                'rescheduled'      => 0,
                'held'             => 0,
                'total_meetings'   => 0,
                'evaluated'        => 0,
                'is_shortage'      => false,
            ];
            $attTone = !empty($pa['is_shortage']) ? 'tone-red' : ($pa['percentage'] < 85.0 ? 'tone-amber' : 'tone-green');
            ?>

            <?php if (!empty($pa['is_shortage']) && empty($viewData['isTeacherDrilldown'])): ?>
            <div class="p-3.5 rounded border border-red-500/40 bg-red-950/40 text-red-200 text-xs flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-exclamation-triangle text-base text-red-400"></i>
                    <div>
                        <strong>Attendance Shortage Alert:</strong> Your attendance (<?php echo $pa['percentage']; ?>%) is below the university 75% threshold. Please meet with your guide immediately.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-3">
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl font-mono-ui"><?php echo count($viewData['actualTeamRoster']); ?>/<?php echo $viewData['maxTeamSize'] ?? 10; ?></div>
                    <div class="text-xs text-muted-ui mt-1 font-semibold">members active</div>
                </div>
                <div class="card stat-tile <?php echo $attTone; ?> p-4">
                    <div class="stat-num text-3xl font-mono-ui"><?php echo $pa['percentage']; ?>%</div>
                    <div class="text-xs text-muted-ui mt-1 font-semibold">
                        <?php echo empty($viewData['isTeacherDrilldown']) ? 'Personal Attendance' : 'Review Attendance Metric'; ?>
                        (<?php echo (int)$pa['present']; ?>P &middot; <?php echo (int)$pa['absent']; ?>A &middot; <?php echo (int)$pa['excused']; ?>E)
                    </div>
                </div>
            </div>

            <!-- Continuous Internal Evaluation (CIE) Marks Summary -->
            <?php
            $se = $viewData['studentEvaluation'] ?? null;
            $es = $viewData['evaluationSheet'] ?? null;
            $isFin = !empty($es['is_finalized']);
            ?>
            <?php if ($se && ($se['total_marks'] !== null || $isFin)): ?>
            <div class="card p-4 border-ui bg-panel">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-ui pb-3 mb-3">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-clipboard-check text-accent text-lg"></i>
                        <div>
                            <h3 class="font-head font-semibold text-sm">Continuous Internal Evaluation (CIE) Marks</h3>
                            <span class="text-[11px] text-muted-ui font-mono-ui">Grading Scheme: 50 Report / 25 Presentation / 25 Viva Q&A</span>
                        </div>
                    </div>
                    <div>
                        <?php if ($isFin): ?>
                            <span class="badge badge-green text-xs font-mono-ui flex items-center gap-1.5 py-1 px-2.5">
                                <i class="fas fa-lock"></i> Finalized Evaluation
                            </span>
                        <?php else: ?>
                            <span class="badge badge-muted text-xs font-mono-ui py-1 px-2.5">
                                Provisional / In Review
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="p-2.5 bg-raised rounded border border-ui">
                        <span class="block text-[11px] text-muted-ui font-semibold uppercase">Project Report</span>
                        <span class="text-lg font-mono-ui font-bold text-white">
                            <?php echo $se['report_marks'] !== null ? number_format((float)$se['report_marks'], 1) : '-'; ?>
                        </span>
                        <span class="block text-[10px] text-muted-ui">/ 50 max</span>
                    </div>

                    <div class="p-2.5 bg-raised rounded border border-ui">
                        <span class="block text-[11px] text-muted-ui font-semibold uppercase">Presentation</span>
                        <span class="text-lg font-mono-ui font-bold text-white">
                            <?php echo $se['presentation_marks'] !== null ? number_format((float)$se['presentation_marks'], 1) : '-'; ?>
                        </span>
                        <span class="block text-[10px] text-muted-ui">/ 25 max</span>
                    </div>

                    <div class="p-2.5 bg-raised rounded border border-ui">
                        <span class="block text-[11px] text-muted-ui font-semibold uppercase">Viva Q&A</span>
                        <span class="text-lg font-mono-ui font-bold text-white">
                            <?php echo $se['qa_marks'] !== null ? number_format((float)$se['qa_marks'], 1) : '-'; ?>
                        </span>
                        <span class="block text-[10px] text-muted-ui">/ 25 max</span>
                    </div>

                    <div class="p-2.5 bg-raised rounded border border-accent/40" style="background: rgba(var(--accent-rgb, 2, 132, 199), 0.1);">
                        <span class="block text-[11px] font-bold uppercase" style="color: var(--accent);">Total Score</span>
                        <span class="text-xl font-mono-ui font-extrabold" style="color: var(--accent);">
                            <?php echo $se['total_marks'] !== null ? number_format((float)$se['total_marks'], 1) : '-'; ?>
                        </span>
                        <span class="block text-[10px] text-muted-ui font-mono-ui">/ 100 max <?php if ($se['percentage'] !== null): ?>(<?php echo $se['percentage']; ?>%)<?php endif; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Classroom Milestones & Countdown -->
            <?php require __DIR__ . '/partials/milestone_widget.php'; ?>

            <!-- Leader Tabs -->
            <div class="flex justify-between items-center border-b border-ui">
                <div class="flex gap-6">
                    <button id="btn-tab-team" class="tab-btn active pb-2" onclick="switchLeaderTab('team')">Team</button>
                    <button id="btn-tab-logs" class="tab-btn pb-2" onclick="switchLeaderTab('logs')">Weekly Logs</button>
                </div>
                <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                <div class="pb-2">
                    <button data-modal-target="#reportModal" onclick="document.getElementById('reportModal').classList.remove('hidden')" class="btn-ui text-xs font-semibold px-3 py-1.5" style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-file-word me-1.5"></i>Generate Report Assistant
                    </button>
                </div>
                <?php endif; ?>
            </div>


            <!-- TAB 2: Team (roster / requests / invite) -->
            <div id="leader-tab-team" class="view-pane flex-1 flex flex-col gap-8">
                <!-- Pending Join Requests -->
                <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                <div>
                    <h3 class="font-head font-semibold mb-4 flex justify-between items-center">
                        Pending Requests
                        <?php if(count($viewData['pendingRequests']) > 0): ?>
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
                                        <?php echo strtoupper(substr($req['username'], 0, 1)); ?>
                                    </div>
                                    <span class="font-semibold text-sm"><?php echo e($req['username']); ?></span>
                                </div>
                                <div class="flex gap-2">
                                      <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                      <form method="POST" action="manage_join_request.php" class="inline">
                                          <?php echo csrf_field(); ?>
                                          <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                          <input type="hidden" name="project_id" value="<?php echo $viewData['myProjectId']; ?>">
                                          <input type="hidden" name="user_id" value="<?php echo $req['id']; ?>">
                                          <input type="hidden" name="action" value="accept">
                                          <button class="btn-ui px-3 py-1.5 text-xs font-semibold bg-accent text-bg hover:opacity-90">Accept</button>
                                      </form>
                                      <form method="POST" action="manage_join_request.php" class="inline">
                                          <?php echo csrf_field(); ?>
                                          <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                          <input type="hidden" name="project_id" value="<?php echo $viewData['myProjectId']; ?>">
                                          <input type="hidden" name="user_id" value="<?php echo $req['id']; ?>">
                                          <input type="hidden" name="action" value="decline">
                                          <button class="btn-ui px-3 py-1.5 text-xs font-semibold border border-ui hover-overlay-medium transition" style="color: var(--danger); border-color: var(--danger);">Decline</button>
                                      </form>
                                      <?php endif; ?>
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
                    <div class="space-y-4">
                        <?php foreach ($viewData['teamRoster'] as $m): ?>
                          <div class="flex items-center gap-4">
                              <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent); color: var(--bg);">
                                  <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                              </div>
                              <div>
                                  <div class="font-semibold text-sm"><?php echo e($m['name']); ?></div>
                                  <div class="text-xs text-muted-ui font-mono-ui"><?php echo e($m['role']); ?></div>
                              </div>
                          </div>
                          <?php endforeach; ?>
                    </div>
                </div>

                <!-- Invite Classmates (real invitations via handle_invitation.php) -->
                <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                <div>
                    <h3 class="font-head font-semibold mb-4">Team Building</h3>
                    <div class="p-6 border border-ui border-dashed rounded text-center">
                        <p class="text-sm text-muted-ui mb-4">You have <?php echo max(0, ($viewData['maxTeamSize'] ?? 10) - count($viewData['actualTeamRoster'])); ?> open slots remaining on your team (Max <?php echo $viewData['maxTeamSize'] ?? 10; ?>).</p>
                        <button onclick="document.getElementById('inviteModal').classList.add('show')" class="btn-ui px-4 py-2 text-sm font-semibold bg-accent-2 text-bg hover:opacity-90">
                            <i class="fas fa-user-plus mr-2"></i> Browse Classmates
                        </button>
                    </div>
                </div>

                <!-- Invite Modal -->
                <div id="inviteModal" class="success-overlay">
                    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-lg shadow-xl pointer-events-auto max-h-[80vh] flex flex-col">
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui">
                            <h2 class="text-xl font-bold font-head">Invite to Project</h2>
                            <button onclick="document.getElementById('inviteModal').classList.remove('show')" class="text-muted-ui hover:text-accent-2 transition"><i class="fas fa-times"></i></button>
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
                                                <?php echo strtoupper(substr($c['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="block font-semibold text-sm"><?php echo e($c['username']); ?></span>
                                                <?php if (isset($c['usn'])): ?>
                                                <span class="block text-xs text-muted-ui font-mono-ui"><?php echo e($c['usn']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                    <button class="px-4 py-1.5 text-xs font-semibold rounded bg-overlay-strong text-muted-ui border border-ui hover:border-accent hover:text-accent transition invite-btn" data-user-id="<?php echo $c['id']; ?>" data-project-id="<?php echo $viewData['myProjectId']; ?>" data-classroom-id="<?php echo $viewData['classroom_id']; ?>">
                                        Invite
                                    </button>
                                    <?php endif; ?>
                                    </div>
                                    <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: Weekly Logs (shared partial — Phase 4) -->
            <div id="leader-tab-logs" class="view-pane flex-1 flex flex-col hidden">
                <?php require __DIR__ . '/partials/weekly_logs.php'; ?>
            </div>

        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const inviteButtons = document.querySelectorAll('.invite-btn');

                inviteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const btn = this;
                        const userId = btn.getAttribute('data-user-id');
                        const projectId = btn.getAttribute('data-project-id');
                        const classroomId = btn.getAttribute('data-classroom-id');

                        // Disable button during request
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

                        const params = {
                            'classroom_id': classroomId,
                            'project_id': projectId,
                            'user_id': userId,
                            'action': 'invite'
                        };
                        if (csrfToken) params.csrf_token = csrfToken;

                        // Send invitation via AJAX
                        fetch('handle_invitation.php', {
                            method: 'POST',
                            headers: headers,
                            body: new URLSearchParams(params)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                btn.innerHTML = '<i class="fas fa-check"></i> Invited';
                                btn.classList.add('text-accent', 'border-accent');
                                btn.classList.remove('text-muted-ui', 'border-ui', 'bg-overlay-strong');
                                btn.style.backgroundColor = 'var(--accent-2)';
                                btn.style.borderColor = 'var(--accent-2)';
                                btn.style.color = 'var(--bg)';
                            } else {
                                btn.innerHTML = '<i class="fas fa-times"></i> Error';
                                btn.classList.add('text-danger', 'border-danger');
                                setTimeout(() => {
                                    btn.innerHTML = 'Invite';
                                    btn.classList.remove('text-danger', 'border-danger');
                                    btn.disabled = false;
                                }, 2000);
                            }
                        })
                        .catch(error => {
                            btn.innerHTML = '<i class="fas fa-times"></i> Error';
                            btn.classList.add('text-danger', 'border-danger');
                            setTimeout(() => {
                                btn.innerHTML = 'Invite';
                                btn.classList.remove('text-danger', 'border-danger');
                                btn.disabled = false;
                            }, 2000);
                        });
                    });
                });
            });
        </script>

        <?php require __DIR__ . '/marks_modal.php'; ?>
