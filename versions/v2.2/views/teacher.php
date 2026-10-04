<!-- ============================================================ -->
        <!-- TEACHER — Marking desk & Live Roster                          -->
        <!-- ============================================================ -->
        <?php
        $totalGroups = count($viewData['projectGroups'] ?? []);
        $nearlyFinishedGroups = count(array_filter($viewData['projectGroups'] ?? [], function($g) {
            return ($g['percent'] ?? 0) >= 90;
        }));
        ?>
        <div class="col-span-4 flex flex-col h-full">
            
            <!-- Teacher Tabs -->
            <div class="flex gap-6 border-b border-ui mb-6">
                <button id="btn-tab-groups" class="tab-btn active pb-2" onclick="switchTeacherTab('groups')">Project Groups</button>
                <button id="btn-tab-roster" class="tab-btn pb-2" onclick="switchTeacherTab('roster')">Classroom</button>
            </div>

            <!-- TAB 1: Project Groups (Ledger) -->
            <div id="tab-groups" class="view-pane flex-1 flex flex-col">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h2 class="text-2xl font-head font-semibold"><?php echo htmlspecialchars($viewData['classroomName'] ?? 'Classroom'); ?></h2>
                        <p class="text-muted-ui text-sm mt-1" id="ledger-stats"><?php echo $totalGroups; ?> groups &middot; <?php echo $nearlyFinishedGroups; ?> nearly finished</p>
                    </div>
                    <div class="flex gap-3">
                        
                        <button onclick="copyInviteCode()" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-copy"></i> Copy Invite Code
                        </button>
                        
                        <button id="sortLedgerBtn" class="ledger-head-btn text-sm font-semibold pb-0.5">
                            Sort by completion <i class="fas fa-arrow-down-short-wide ms-1 text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="card flex-1 flex flex-col">
                    <div class="grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui">
                        <span>Group</span><span class="w-20 text-right">Members</span><span class="w-40">Completion</span><span class="w-16 text-right">Status</span>
                    </div>
                    <div id="ledger-body" class="flex-1">
                        <?php if (empty($viewData['projectGroups'])): ?>
                        <div class="py-12 text-center text-muted-ui text-sm italic" id="empty-groups-state">
                            Waiting for students to create project groups...
                        </div>
                        <?php endif; ?>
                        <?php foreach ($viewData['projectGroups'] as $index => $g):
                            $near = $g['percent'] >= 90;
                            $ink = $g['percent'] < 50 ? 'var(--status-red)' : ($near ? 'var(--status-green)' : 'var(--status-amber)');
                            $statusWord = $g['percent'] < 50 ? 'At risk' : ($near ? 'Nearly done' : 'On track');
                            $statusColor = $ink;
                        ?>
                        <div class="ledger-item flex flex-col" data-percent="<?php echo $g['percent']; ?>">
                            <div class="ledger-row grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-4 items-center cursor-pointer hover-overlay-subtle transition" onclick="document.getElementById('details-<?php echo $index; ?>').classList.toggle('hidden'); document.getElementById('chevron-<?php echo $index; ?>').classList.toggle('rotate-180')">
                                <div class="flex items-center gap-3 min-w-0">
                                    <?php if ($near): ?>
                                    <span class="seal flex-none"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                    <span class="font-semibold truncate"><?php echo htmlspecialchars($g['name']); ?></span>
                                    <i class="fas fa-chevron-down text-xs text-muted-ui ml-2 transition-transform duration-200" id="chevron-<?php echo $index; ?>"></i>
                                </div>
                                <span class="w-20 text-right text-sm text-muted-ui"><?php echo (int)$g['members']; ?></span>
                                <div class="w-40">
                                    <?php if (!empty($g['phase_stats'])): ?>
                                        <div class="flex gap-1 mb-1">
                                            <?php foreach (['Synopsis', 'Phase 1', 'Phase 2', 'Final Demo'] as $m): ?>
                                                <?php 
                                                $pPct = $g['phase_stats'][$m] ?? 0; 
                                                $pColor = $pPct === 100 ? 'var(--accent)' : ($pPct > 0 ? 'var(--accent-2)' : 'var(--border)');
                                                ?>
                                                <div class="flex-1 h-2 rounded-sm" style="background: <?php echo $pColor; ?>;" title="<?php echo $m . ': ' . $pPct . '%'; ?>"></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-xs font-mono-ui text-muted-ui flex justify-between">
                                            <span><?php echo (int)$g['percent']; ?>% overall</span>
                                        </span>
                                    <?php else: ?>
                                        <div class="ink-bar-track w-full mb-1">
                                            <div class="ink-bar-fill" style="width: <?php echo (int)$g['percent']; ?>%; background: <?php echo $ink; ?>;"></div>
                                        </div>
                                        <span class="text-xs font-mono-ui text-muted-ui"><?php echo (int)$g['percent']; ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <span class="w-16 text-right text-sm font-semibold" style="color: <?php echo $statusColor; ?>;"><?php echo $statusWord; ?></span>
                            </div>
                            <div id="details-<?php echo $index; ?>" class="hidden px-14 py-4 bg-overlay-subtle border-b border-ui text-sm">
                                <h4 class="font-semibold mb-1">Project Description</h4>
                                <p class="text-muted-ui mb-3"><?php echo htmlspecialchars($g['desc'] ?? 'No description provided.'); ?></p>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <h4 class="font-semibold mb-1">Team Members</h4>
                                        <ul class="list-disc list-inside text-muted-ui mb-4">
                                            <?php foreach ($g['member_names'] ?? [] as $member): ?>
                                                <li><?php echo htmlspecialchars($member); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <h4 class="font-semibold mb-1">Assigned Mentor</h4>
                                        <?php if (!empty($viewData['isCoordinator'])): ?>
                                        <div class="flex gap-2 items-center mb-4">
                                            <form method="POST" action="assign_mentor.php" class="flex gap-2 items-center">
                                                <input type="hidden" name="project_id" value="<?php echo $g['id']; ?>">
                                                <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                                                <select name="mentor_id" class="form-input text-xs py-1 px-2" style="width: auto;">
                                                    <option value="">-- No Mentor --</option>
                                                    <?php foreach ($viewData['availableMentors'] ?? [] as $mentor): ?>
                                                        <option value="<?php echo $mentor['id']; ?>" <?php echo ($g['mentor_id'] == $mentor['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($mentor['username']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn-ui px-3 py-1 text-xs hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">Assign</button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted-ui text-sm mb-4">
                                            <?php echo !empty($g['mentor_name']) ? htmlspecialchars($g['mentor_name']) : 'Unassigned'; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php ?>
                                      <a href="dashboard.php?project_id=<?php echo $g['id']; ?>&classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                                        View Full Report <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Classroom Roster & Live Join Demo -->
            <div id="tab-roster" class="view-pane flex-1 flex flex-col hidden">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h2 class="text-2xl font-head font-semibold"><?php echo htmlspecialchars($viewData['classroomName'] ?? 'Classroom'); ?></h2>
                        <p class="text-muted-ui text-sm mt-1">Invite Code: <span class="font-mono-ui font-semibold px-2 py-1 bg-overlay-subtle rounded"><?php echo htmlspecialchars($viewData['inviteCode'] ?? 'XXXXXX'); ?></span></p>
                    </div>
                    
                    <button onclick="copyInviteCode()" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-copy"></i> Copy Invite Code
                    </button>
                    
                </div>
                
                <div class="card p-5 mb-6">
                    <div class="flex justify-between items-center border-b border-ui pb-2 mb-4">
                        <h3 class="font-semibold text-lg font-head">Faculty & Mentors</h3>
                    </div>
                    <?php if (!empty($viewData['isCoordinator'])): ?>
                    <form method="POST" action="add_mentor_to_classroom.php" class="flex gap-3 mb-6">
                        <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                        <input type="text" name="mentor_username" class="form-input flex-1" placeholder="Enter faculty username to invite as Mentor..." required>
                        <button type="submit" class="btn-ui px-4 py-2 font-semibold hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg);">
                            Add Mentor
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="flex flex-col gap-3">
                        <?php foreach ($viewData['availableMentors'] ?? [] as $mentor): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent-2); color: var(--bg);">
                                    <?php echo strtoupper(substr($mentor['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm"><?php echo htmlspecialchars($mentor['username']); ?></div>
                                    <div class="text-xs text-muted-ui font-mono-ui uppercase tracking-wider">Admin / Mentor</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card p-5">
                    <h3 class="font-semibold text-lg font-head border-b border-ui pb-2 mb-4">Students</h3>
                    <div class="grid grid-cols-[auto_1fr_auto] gap-4 text-xs font-semibold text-muted-ui font-mono-ui border-b border-ui pb-2">
                        <span class="w-8"></span><span>Student Info</span><span>Status</span>
                    </div>
                    <div id="live-roster-list" class="flex flex-col mt-2">
                        <?php if (empty($viewData['classroomRoster'])): ?>
                            <div class="py-6 text-center text-muted-ui text-sm italic" id="empty-roster-state">
                                No students have joined this classroom yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($viewData['classroomRoster'] as $student): ?>
                                <div class="grid grid-cols-[auto_1fr_auto] gap-4 py-3 border-b border-ui items-center view-pane">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent); color: var(--bg);">
                                        <?php echo strtoupper(substr($student['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm"><?php echo htmlspecialchars($student['username']); ?></div>
                                        <div class="text-xs text-muted-ui font-mono-ui tracking-wide"><?php echo htmlspecialchars($student['usn'] ?? 'No USN'); ?></div>
                                    </div>
                                    <div>
                                        <?php if ($student['project_name']): ?>
                                            <span class="badge badge-green text-[10px] uppercase tracking-wider">In <?php echo htmlspecialchars($student['project_name']); ?></span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-[10px] font-semibold rounded uppercase tracking-wider bg-overlay-medium text-muted-ui">Unassigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        

        <script>
            function copyInviteCode() {
                const code = "<?php echo htmlspecialchars($viewData['inviteCode'] ?? 'XXXXXX'); ?>";
                navigator.clipboard.writeText(code).then(() => {
                    const toast = document.getElementById('toast');
                    if (toast) {
                        toast.querySelector('h4').innerText = "Code Copied";
                        toast.querySelector('p').innerText = `Share code ${code} with students to join.`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                });
            }
        </script>

