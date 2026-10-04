<!-- ============================================================ -->
        <!-- PROJECT LEADER — Control room: stats, ticker, roster          -->
        <!-- ============================================================ -->
        <div class="<?php echo $viewData['isNewlyCreated'] ? 'col-span-4' : 'col-span-3'; ?> flex flex-col h-full gap-4">

            <?php if (!empty($viewData['isTeacherDrilldown'])): ?>
            <div class="flex items-center justify-between bg-overlay-subtle border border-ui rounded px-4 py-3 mb-2">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 text-xs font-bold rounded uppercase tracking-wider" style="background: var(--accent-2); color: var(--bg);">Teacher Mode</span>
                    <span class="font-semibold text-sm">Read-Only Audit: <?php echo htmlspecialchars($viewData['myProject']['name']); ?></span>
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
                    <div class="stat-num text-3xl"><?php echo $viewData['daysToDeadline']; ?></div>
                    <div class="text-xs text-muted-ui mt-1">days to deadline</div>
                </div>
            </div>

            <!-- Leader Tabs -->
            <div class="flex gap-6 border-b border-ui">
                <button id="btn-tab-board" class="tab-btn active pb-2" onclick="switchLeaderTab('board')">My Board</button>
                <button id="btn-tab-team" class="tab-btn pb-2" onclick="switchLeaderTab('team')">Team</button>
                <button id="btn-tab-logs" class="tab-btn pb-2" onclick="switchLeaderTab('logs')">Weekly Logs</button>
            </div>

            <!-- TAB 1: My Board (kanban / calendar) -->
            <div id="leader-tab-board" class="view-pane flex-1 flex flex-col">
                <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                    <div class="flex items-center gap-1">
                        <button id="toggleKanbanBtn" class="tab-btn active px-3 py-2">board.kanban</button>
                        <button id="toggleCalendarBtn" class="tab-btn px-3 py-2">calendar.month</button>
                    </div>
                    <div class="flex gap-2">
                    <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                    <button onclick="document.getElementById('addTaskModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent); color: var(--bg);">
                        <i class="fas fa-plus me-2"></i>Add Task
                    </button>
                    <button class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-magic me-2"></i>Generate Friday wrap-up
                    </button>
                    <button class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                    </button>
                    <?php endif; ?>
                </div>
                </div>

                <div id="kanban-view" class="view-pane grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                    <div class="card p-4 flex flex-col">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">To do <span class="bg-raised border border-ui px-2 text-xs py-0.5" style="border-radius: var(--radius);"><?php echo count($viewData['tasks']["todo"] ?? []); ?></span></h3>
                        <div id="todo-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php foreach (($viewData['tasks']['todo'] ?? []) as $task): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);" data-task-id="<?php echo $task['id']; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                                <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                            </div>
                            <div class="flex justify-between items-start mb-1">
                                            <p class="text-sm font-semibold pr-2"><?php echo htmlspecialchars($task['title']); ?></p>
                                            <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                            <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                                            <?php endif; ?>
                                        </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent-2);">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">In progress <span class="badge badge-accent-2"><?php echo count($viewData['tasks']["inprogress"] ?? []); ?></span></h3>
                        <div id="inprogress-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php foreach (($viewData['tasks']['inprogress'] ?? []) as $task): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius); border-left: 2px solid var(--accent-2);" data-task-id="<?php echo $task['id']; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                                <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                            </div>
                            <div class="flex justify-between items-start mb-1">
                                            <p class="text-sm font-semibold pr-2"><?php echo htmlspecialchars($task['title']); ?></p>
                                            <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                            <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                                            <?php endif; ?>
                                        </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent);">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">Done <span class="badge badge-accent"><?php echo count($viewData['tasks']["done"] ?? []); ?></span></h3>
                        <div id="done-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php foreach (($viewData['tasks']['done'] ?? []) as $task): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab opacity-60" style="border-radius: var(--radius);" data-task-id="<?php echo $task['id']; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);"><?php echo htmlspecialchars($task['assigned_to'] ? ($task['assignee_name'] ?? 'Assigned') : 'Unassigned'); ?></span>
                                <?php if ($task['priority'] === 'high'): ?><i class="fas fa-flag text-danger text-xs"></i><?php endif; ?>
                            </div>
                            <div class="flex justify-between items-start mb-1">
                                            <p class="text-sm font-semibold pr-2 line-through"><?php echo htmlspecialchars($task['title']); ?></p>
                                            <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                            <button onclick="openUploadModal(<?php echo $task['id']; ?>)" class="text-muted-ui hover:text-accent transition flex-shrink-0" title="Upload Deliverable"><i class="fas fa-paperclip text-xs"></i></button>
                                            <?php endif; ?>
                                        </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

                <div id="calendar-view" class="view-pane card p-4 flex-1 hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold font-head"><?php echo $viewData['monthLabel']; ?></h3>
                        <div class="flex items-center gap-3 text-xs text-muted-ui font-mono-ui">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--danger);"></span>high</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--accent-2);"></span>normal</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-2 text-center text-xs text-muted-ui font-semibold mb-2 font-mono-ui">
                        <div>mon</div><div>tue</div><div>wed</div><div>thu</div><div>fri</div><div>sat</div><div>sun</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        <?php
                        for ($b = 1; $b < $startWeekday; $b++) echo '<div class="cal-cell"></div>';
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $isToday = ($d === $todayNum);
                            echo '<div class="cal-cell bg-raised border border-ui p-1.5 flex flex-col ' . ($isToday ? 'is-today' : '') . '" style="border-radius: var(--radius);">';
                            echo '<span class="text-xs font-semibold font-mono-ui ' . ($isToday ? 'text-accent-2' : 'text-muted-ui') . '">' . $d . '</span><div class="mt-1 space-y-1">';
                            if (isset($viewData['calendarTasks'][$d])) {
                                foreach ($viewData['calendarTasks'][$d] as $t) {
                                    $bg = $t['priority'] === 'high' ? 'rgba(var(--danger-rgb), 0.18)' : 'rgba(var(--accent-2-rgb), 0.18)';
                                    $fg = $t['priority'] === 'high' ? 'var(--danger)' : 'var(--accent-2)';
                                    echo '<div class="cal-pill" style="background:' . $bg . '; color:' . $fg . ';" title="' . htmlspecialchars($t['title']) . '">' . htmlspecialchars($t['title']) . '</div>';
                                }
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
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
                                    <span class="font-semibold text-sm"><?php echo htmlspecialchars($req['username']); ?></span>
                                </div>
                                <div class="flex gap-2">
                                      <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                      <form method="POST" action="manage_join_request.php" class="inline">
                                          <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                                          <input type="hidden" name="project_id" value="<?php echo $viewData['myProjectId']; ?>">
                                          <input type="hidden" name="user_id" value="<?php echo $req['id']; ?>">
                                          <input type="hidden" name="action" value="accept">
                                          <button class="btn-ui px-3 py-1.5 text-xs font-semibold bg-accent text-bg hover:opacity-90">Accept</button>
                                      </form>
                                      <form method="POST" action="manage_join_request.php" class="inline">
                                          <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
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
                              <span class="status-dot <?php echo $m['status'] === 'red' ? 'red' : 'green'; ?>" aria-hidden="true"></span>
                              <div class="w-48 flex-none">
                                  <div class="font-semibold text-sm"><?php echo htmlspecialchars($m['name']); ?></div>
                                  <div class="text-xs text-muted-ui font-mono-ui"><?php echo htmlspecialchars($m['role']); ?></div>
                              </div>
                              <div class="flex-1 min-w-0">
                                  <div class="text-sm truncate mb-1 <?php echo $m['task'] === 'No tasks' ? 'text-muted-ui italic' : ''; ?>"><?php echo htmlspecialchars($m['task']); ?></div>
                                  <div class="roster-bar-track h-1.5 w-full">
                                      <div class="roster-bar-fill h-full" style="width: <?php echo $m['percent']; ?>%; background: var(--accent);"></div>
                                  </div>
                              </div>
                          </div>
                          <?php endforeach; ?>
                    </div>
                </div>

                <!-- Invite Classmates (Presentation Simulation) -->
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

                <!-- Simulation Modal -->
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
                                                <span class="block font-semibold text-sm"><?php echo htmlspecialchars($c['username']); ?></span>
                                                <?php if (isset($c['usn'])): ?>
                                                <span class="block text-xs text-muted-ui font-mono-ui"><?php echo htmlspecialchars($c['usn']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (empty($viewData['isTeacherDrilldown'])): ?>
                                    <button class="px-4 py-1.5 text-xs font-semibold rounded bg-overlay-strong text-muted-ui border border-ui hover:border-accent hover:text-accent transition" onclick="this.innerHTML='<i class=\'fas fa-check\'></i> Sent'; this.classList.add('text-accent', 'border-accent');">
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

            <!-- TAB 3: Weekly Logs -->
            <div id="leader-tab-logs" class="view-pane flex-1 flex flex-col hidden">
                <div class="card p-5 flex-1 overflow-y-auto">
                    <h3 class="font-head font-semibold text-lg mb-4">Weekly Submissions</h3>
                    <?php 
                    $startDate = !empty($viewData['classroomStartDate']) ? new DateTime($viewData['classroomStartDate']) : null;
                    $endDate = !empty($viewData['classroomEndDate']) ? new DateTime($viewData['classroomEndDate']) : null;
                    
                    if (!$startDate || !$endDate): ?>
                        <div class="py-12 text-center text-muted-ui text-sm italic border border-ui rounded bg-raised">
                            The classroom coordinator has not set start and end dates. Weekly logs are disabled.
                        </div>
                    <?php else: 
                        $diff = $startDate->diff($endDate);
                        $totalWeeks = ceil($diff->days / 7);
                        if ($totalWeeks == 0) $totalWeeks = 1;

                        for ($w = 1; $w <= $totalWeeks; $w++):
                            $log = $viewData['weeklyLogs'][$w] ?? null;
                            $status = $log ? ($log['review_status'] ?? 'pending') : 'unsubmitted';
                            
                            $statusBadge = '';
                            if ($status === 'unsubmitted') $statusBadge = '<span class="badge badge-muted">Not Submitted</span>';
                            elseif ($status === 'pending') $statusBadge = '<span class="badge badge-accent">Pending Review</span>';
                            elseif ($status === 'approved') $statusBadge = '<span class="badge badge-green">Approved</span>';
                            elseif ($status === 'revision_needed') $statusBadge = '<span class="badge badge-danger">Revision Flagged</span>';
                    ?>
                        <div class="border border-ui rounded p-4 mb-4 bg-raised">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold">Week <?php echo $w; ?></h4>
                                <?php echo $statusBadge; ?>
                            </div>
                            
                            <?php if (!$log): ?>
                                <p class="text-sm text-muted-ui mb-3">No submission for this week.</p>
                            <?php else: ?>
                                <p class="text-sm font-semibold mt-2">Work Summary:</p>
                                <p class="text-sm text-muted-ui mb-2"><?php echo nl2br(htmlspecialchars($log['work_summary'] ?? '')); ?></p>
                                
                                <p class="text-sm font-semibold">Next Steps:</p>
                                <p class="text-sm text-muted-ui mb-3"><?php echo nl2br(htmlspecialchars($log['next_steps'] ?? '')); ?></p>
                                
                                <?php if (!empty($log['files'])): ?>
                                    <p class="text-sm font-semibold">Attachments:</p>
                                    <div class="flex flex-wrap gap-2 mt-1 mb-3">
                                        <?php foreach ($log['files'] as $f): ?>
                                            <a href="<?php echo htmlspecialchars($f['file_path']); ?>" download class="inline-flex items-center gap-2 px-3 py-1 text-xs rounded bg-overlay-subtle border border-ui hover:border-accent transition text-muted-ui hover:text-accent">
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

                            <?php if ((!$log || $status === 'revision_needed') && empty($viewData['isTeacherDrilldown'])): ?>
                                <div class="<?php echo $log ? 'mt-4 pt-3 border-t border-ui' : ''; ?>">
                                    <button onclick="openWeeklyLogModal(<?php echo $w; ?>)" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                                        <i class="fas fa-upload me-2"></i> <?php echo $log ? 'Submit Revision' : 'Submit Weekly Log'; ?>
                                    </button>
                                </div>
                            <?php endif; ?>

                                <?php if (!empty($viewData['isTeacherDrilldown']) && (($viewData['isCoordinator'] ?? false) || (($viewData['myProject']['mentor_id'] ?? 0) == $_SESSION['user_id']))): 
                                    $logJson = $log ? json_encode([
                                        'status' => $log['review_status'] ?? 'pending',
                                        'remarks' => $log['mentor_remarks'] ?? '',
                                        'attendance' => array_column($log['attendance'] ?? [], 'present', 'user_id')
                                    ]) : 'null';
                                ?>
                                    <div class="mt-4 flex justify-end pt-3 border-t border-ui">
                                        <button onclick="openMentorReviewModal(<?php echo $w; ?>, <?php echo $log ? $log['id'] : 'null'; ?>, this.getAttribute('data-log'))" data-log="<?php echo htmlspecialchars($logJson); ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent-2); border-color: var(--accent-2);">
                                            <i class="fas fa-clipboard-check me-2"></i> Mentor Review
                                        </button>
                                    </div>
                                <?php endif; ?>
                        </div>
                    <?php endfor; endif; ?>
                </div>
            </div>

        </div>



