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
                <button id="btn-tab-phases" class="tab-btn pb-2" onclick="switchTeacherTab('phases')">Phases</button>
            </div>

            <!-- TAB 3: Phase Schedule (1 phase = 1 week; rename/merge) -->
            <div id="tab-phases" class="view-pane flex-1 flex flex-col hidden">
                <?php if (empty($viewData['hasSchedule'])): ?>
                    <div class="card p-10 text-center border-dashed flex flex-col items-center justify-center">
                        <i class="fas fa-calendar-xmark text-3xl text-muted-ui opacity-50 mb-3"></i>
                        <h3 class="font-head font-semibold mb-2">No schedule set</h3>
                        <p class="text-sm text-muted-ui max-w-md">
                            Phases are derived automatically from this classroom's start and end dates.
                            Set both dates when creating or editing the classroom to generate them.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <h2 class="text-2xl font-head font-semibold">Phase Schedule</h2>
                        <p class="text-muted-ui text-sm mt-1">
                            <?php echo (int)$viewData['totalWeeks']; ?> phases &middot; one phase per week, derived from
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($viewData['classroomStartDate']))); ?>
                            &ndash;
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($viewData['classroomEndDate']))); ?>.
                        </p>
                    </div>

                    <?php if (empty($viewData['isCoordinator'])): ?>
                        <div class="card p-4 mb-4 flex items-start gap-3" style="border-color: var(--accent-2);">
                            <i class="fas fa-lock mt-0.5" style="color: var(--accent-2);"></i>
                            <p class="text-sm text-muted-ui">Only the classroom coordinator can rename or merge phases.</p>
                        </div>
                    <?php endif; ?>

                    <div class="card flex-1 overflow-y-auto p-4">
                        <div class="space-y-3">
                            <?php foreach ($viewData['phases'] as $p): ?>
                            <div class="flex items-center gap-3 p-3 bg-raised border <?php echo $p['is_current'] ? 'border-accent' : 'border-ui'; ?> rounded flex-wrap">
                                <div class="w-14 flex-none text-center">
                                    <span class="text-xs font-mono-ui font-semibold"><?php echo (int)$p['week_number']; ?></span>
                                </div>
                                <div class="flex-1 min-w-[160px]">
                                    <?php if (!empty($viewData['isCoordinator'])): ?>
                                        <form method="POST" action="manage_phase.php" class="flex gap-2 items-center">
                                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                                            <input type="hidden" name="week_number" value="<?php echo (int)$p['week_number']; ?>">
                                            <input type="hidden" name="action" value="rename">
                                            <input type="text" name="label" value="<?php echo htmlspecialchars($p['label']); ?>" maxlength="120" required
                                                   class="bg-panel border border-ui rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:border-accent transition">
                                            <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">Save</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="font-semibold text-sm"><?php echo htmlspecialchars($p['label']); ?></span>
                                    <?php endif; ?>
                                    <span class="block text-xs text-muted-ui font-mono-ui mt-1">
                                        <?php echo htmlspecialchars(date('M j', strtotime($p['date_from'])) . ' – ' . date('M j', strtotime($p['date_to']))); ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 flex-none flex-wrap justify-end">
                                    <?php if ($p['is_current']): ?>
                                        <span class="badge" style="background: var(--accent-2); color: var(--bg);">Current</span>
                                    <?php endif; ?>

                                    <?php if (!empty($p['merged_from'])): ?>
                                        <span class="badge badge-accent" title="Tasks and weekly logs from these phases roll up into this one">
                                            + Week <?php echo htmlspecialchars(implode(', ', $p['merged_from'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($viewData['isCoordinator'])): ?>
                                <div class="flex items-center gap-2 flex-none">
                                    <?php if ($p['is_merged']): ?>
                                        <span class="badge badge-muted">Merged into Week <?php echo (int)$p['merged_into_week']; ?></span>
                                        <form method="POST" action="manage_phase.php" class="inline">
                                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                                            <input type="hidden" name="week_number" value="<?php echo (int)$p['week_number']; ?>">
                                            <input type="hidden" name="action" value="unmerge">
                                            <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">Unmerge</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="manage_phase.php" class="flex items-center gap-2">
                                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                                            <input type="hidden" name="week_number" value="<?php echo (int)$p['week_number']; ?>">
                                            <input type="hidden" name="action" value="merge">
                                            <select name="target_week" class="bg-panel border border-ui rounded px-2 py-1.5 text-xs focus:outline-none focus:border-accent transition">
                                                <option value="">Merge into&hellip;</option>
                                                <?php foreach ($viewData['phases'] as $target): ?>
                                                    <?php
                                                    // Skip self, already-merged weeks, and weeks that collect
                                                    // others (merging into those would strand them).
                                                    if ($target['week_number'] == $p['week_number']) continue;
                                                    if ($target['is_merged']) continue;
                                                    if (!empty($target['merged_from'])) continue;
                                                    ?>
                                                    <option value="<?php echo (int)$target['week_number']; ?>">
                                                        <?php echo htmlspecialchars($target['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="color: var(--accent-2); border-color: var(--accent-2);">Merge</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
                        <div class="py-12 text-center text-muted-ui text-sm" id="empty-groups-state">
                            <div class="flex flex-col items-center gap-3 mb-4">
                                <i class="fas fa-users text-muted-ui text-2xl"></i>
                            </div>
                            <p class="font-medium">Waiting for students to create project groups...</p>
                            <p class="text-sm text-muted-ui">Once students form teams, their projects will appear here.</p>
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
                                    <?php if (!empty($g['open_issues'])): ?>
                                    <span class="badge badge-danger flex-none" title="Open blockers raised by this team"><i class="fas fa-triangle-exclamation me-1"></i><?php echo (int)$g['open_issues']; ?> blocker<?php echo $g['open_issues'] > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-down text-xs text-muted-ui ml-2 transition-transform duration-200" id="chevron-<?php echo $index; ?>"></i>
                                </div>
                                <span class="w-20 text-right text-sm text-muted-ui"><?php echo (int)$g['members']; ?></span>
                                <div class="w-40">
                                    <?php if (!empty($g['phase_stats']) && !empty($g['has_schedule'])): ?>
                                        <div class="flex gap-1 mb-1">
                                            <?php foreach ($g['phase_stats'] as $ws):
                                                $pPct = $ws['percent']; // null = no tasks scheduled yet
                                                if ($pPct === null) {
                                                    $pColor = 'var(--border)';
                                                } elseif ($pPct === 100) {
                                                    $pColor = 'var(--accent)';
                                                } elseif ($pPct > 0) {
                                                    $pColor = 'var(--accent-2)';
                                                } else {
                                                    $pColor = 'var(--border)';
                                                }
                                                $wTitle = $ws['label'] . ($pPct === null ? ': no tasks yet' : ': ' . $pPct . '%');
                                            ?>
                                                <div class="flex-1 h-2 rounded-sm" style="background: <?php echo $pColor; ?>; <?php echo !empty($ws['is_current']) ? 'outline: 1px solid var(--accent-2);' : ''; ?>" title="<?php echo htmlspecialchars($wTitle); ?>"></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-xs font-mono-ui text-muted-ui flex justify-between">
                                            <span><?php echo (int)$g['percent']; ?>% overall</span>
                                            <span><?php echo count($g['phase_stats']); ?> phases</span>
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

            <!-- TAB 2: Classroom Roster -->
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
                            <div class="py-6 text-center text-muted-ui text-sm" id="empty-roster-state">
                                <div class="flex flex-col items-center gap-3 mb-4">
                                    <i class="fas fa-user-friends text-muted-ui text-2xl"></i>
                                </div>
                                <p class="font-medium">No students have joined this classroom yet.</p>
                                <p class="text-sm text-muted-ui">Share the invite code to get started.</p>
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
                const onCopied = () => showToast(`Share code ${code} with students to join.`, 'Code Copied');

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(code).then(onCopied).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }

                function fallbackCopy() {
                    const ta = document.createElement('textarea');
                    ta.value = code;
                    ta.style.cssText = 'position:fixed;opacity:0;';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                        document.execCommand('copy');
                        onCopied();
                    } catch (e) {
                        alert('Invite code: ' + code);
                    }
                    document.body.removeChild(ta);
                }
            }
        </script>

