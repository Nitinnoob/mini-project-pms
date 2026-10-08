<!-- ============================================================ -->
        <!-- PROJECT LEADER — Control room: stats, ticker, roster          -->
        <!-- ============================================================ -->
        <div class="<?php echo $viewData['isNewlyCreated'] ? 'col-span-4' : 'col-span-3'; ?> flex flex-col h-full gap-4">

            <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
            <div class="flex items-center justify-between bg-overlay-subtle border border-ui rounded px-4 py-3 mb-2">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 text-xs font-bold rounded uppercase tracking-wider" style="background: var(--accent-2); color: var(--bg);">Teacher Mode</span>
                    <span class="font-semibold text-sm">Read-Only Audit: <?php echo e($viewData['myProject']['name']); ?></span>
                </div>
                <a href="dashboard.php?classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent-2); border-color: var(--accent-2);">
                    <i class="fas fa-arrow-left"></i> Back to Ledger
                </a>
            </div>
            <?php endif; ?>


            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card stat-tile tone-red p-4">
                    <div class="stat-num text-3xl"><?php echo $viewData['blockerCount']; ?></div>
                    <div class="text-xs text-muted-ui mt-1">pending issues</div>
                </div>
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl"><?php echo count($viewData['actualTeamRoster']); ?>/<?php echo $viewData['maxTeamSize'] ?? 10; ?></div>
                    <div class="text-xs text-muted-ui mt-1">members active</div>
                </div>
                <div class="card stat-tile tone-amber p-4">
                    <div class="stat-num text-3xl"><?php echo $viewData['avgVelocity']; ?>%</div>
                    <div class="text-xs text-muted-ui mt-1">overall completion</div>
                </div>
                <div class="card stat-tile p-4">
                    <div class="stat-num text-3xl"><?php echo e((string)$viewData['daysToDeadline']); ?></div>
                    <div class="text-xs text-muted-ui mt-1"><?php echo e($viewData['deadlineLabel']); ?></div>
                </div>
            </div>

            <?php require __DIR__ . '/partials/issues_panel.php'; ?>

            <!-- Leader Tabs -->
            <div class="flex gap-6 border-b border-ui">
                <button id="btn-tab-board" class="tab-btn active pb-2" onclick="switchLeaderTab('board')">My Board</button>
                <button id="btn-tab-team" class="tab-btn pb-2" onclick="switchLeaderTab('team')">Team</button>
                <button id="btn-tab-logs" class="tab-btn pb-2" onclick="switchLeaderTab('logs')">Weekly Logs</button>
            </div>

            <!-- TAB 1: My Board (kanban / calendar) -->
            <?php $isCalendar = ($viewData['activeBoardView'] ?? 'kanban') === 'calendar'; ?>
            <div id="leader-tab-board" class="view-pane flex-1 flex flex-col">
                <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                    <div class="flex items-center gap-1">
                        <button id="toggleKanbanBtn" class="tab-btn px-3 py-2 <?php echo !$isCalendar ? 'active' : ''; ?>">Board</button>
                        <button id="toggleCalendarBtn" class="tab-btn px-3 py-2 <?php echo $isCalendar ? 'active' : ''; ?>">Calendar</button>
                    </div>
                    <div class="flex gap-2">
                    <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                    <button data-modal-target="#addTaskModal" onclick="document.getElementById('addTaskModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent); color: var(--bg);">
                        <i class="fas fa-plus me-2"></i>Add Task
                    </button>
                    <button data-modal-target="#reportModal" onclick="document.getElementById('reportModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-file-word me-2"></i>Generate Report Assistant
                    </button>
                    <button data-modal-target="#escalationModal" onclick="document.getElementById('escalationModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                    </button>
                    <?php endif; ?>
                </div>
                </div>

                <?php require __DIR__ . '/partials/board.php'; ?>

            </div>

            <!-- TAB 2: Team (roster / requests / invite) -->
            <div id="leader-tab-team" class="view-pane flex-1 flex flex-col gap-8 hidden">
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
                              <span class="status-dot <?php echo in_array($m['status'], ['red', 'amber'], true) ? $m['status'] : 'green'; ?>" title="<?php echo e($m['status_note'] ?? ''); ?>" aria-label="<?php echo e($m['status_note'] ?? ''); ?>"></span>
                              <div class="w-48 flex-none">
                                  <div class="font-semibold text-sm"><?php echo e($m['name']); ?></div>
                                  <div class="text-xs text-muted-ui font-mono-ui"><?php echo e($m['role']); ?></div>
                              </div>
                              <div class="flex-1 min-w-0">
                                  <div class="text-sm truncate mb-1 <?php echo $m['task'] === 'No tasks' ? 'text-muted-ui italic' : ''; ?>"><?php echo e($m['task']); ?></div>
                                  <div class="roster-bar-track h-1.5 w-full">
                                      <div class="roster-bar-fill h-full" style="width: <?php echo $m['percent']; ?>%; background: var(--accent);"></div>
                                  </div>
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



