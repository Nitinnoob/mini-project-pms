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
                        <?php if ($viewData['isDemo']): ?>
                        <button onclick="simulateGroups()" class="btn-ui px-4 py-1.5 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-play"></i> Simulate Groups
                        </button>
                        <?php else: ?>
                        <button onclick="copyInviteCode()" class="btn-ui px-4 py-1.5 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-copy"></i> Copy Invite Code
                        </button>
                        <?php endif; ?>
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
                        <?php if (empty($viewData['projectGroups']) && !$viewData['isDemo']): ?>
                        <div class="py-12 text-center text-muted-ui text-sm italic" id="empty-groups-state">
                            Waiting for students to create project groups...
                        </div>
                        <?php elseif ($viewData['isDemo']): ?>
                        <div class="py-12 text-center text-muted-ui text-sm italic" id="empty-groups-state">
                            Click 'Simulate Groups' to watch the incoming data feed...
                        </div>
                        <?php endif; ?>
                        <?php foreach ($viewData['projectGroups'] as $index => $g):
                            $near = $g['percent'] >= 90;
                            $ink = $g['percent'] < 50 ? 'var(--status-red)' : ($near ? 'var(--status-green)' : 'var(--status-amber)');
                            $statusWord = $g['percent'] < 50 ? 'At risk' : ($near ? 'Nearly done' : 'On track');
                            $statusColor = $ink;
                        ?>
                        <div class="ledger-item flex flex-col" data-percent="<?php echo $g['percent']; ?>">
                            <div class="ledger-row grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-4 items-center cursor-pointer hover:bg-black/5 transition" onclick="document.getElementById('details-<?php echo $index; ?>').classList.toggle('hidden'); document.getElementById('chevron-<?php echo $index; ?>').classList.toggle('rotate-180')">
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
                            <div id="details-<?php echo $index; ?>" class="hidden px-14 py-4 bg-black/5 border-b border-ui text-sm">
                                <h4 class="font-semibold mb-1">Project Description</h4>
                                <p class="text-muted-ui mb-3"><?php echo htmlspecialchars($g['desc'] ?? 'No description provided.'); ?></p>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <h4 class="font-semibold mb-1">Team Members</h4>
                                        <ul class="list-disc list-inside text-muted-ui">
                                            <?php foreach ($g['member_names'] ?? [] as $member): ?>
                                                <li><?php echo htmlspecialchars($member); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php if ($viewData['isDemo']): ?>
                                      <a href="dashboard.php?classroom_id=demo&demo_view=TeacherDrilldown"
                                      <?php else: ?>
                                      <a href="dashboard.php?project_id=<?php echo $g['id']; ?>&classroom_id=<?php echo urlencode($viewData['classroom_id']); ?><?php echo !empty($viewData['isDemo']) ? '&demo_view=TeacherDrilldown' : ''; ?>"
                                      <?php endif; ?> class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
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
                        <p class="text-muted-ui text-sm mt-1">Invite Code: <span class="font-mono-ui font-semibold px-2 py-1 bg-black/5 rounded"><?php echo htmlspecialchars($viewData['inviteCode'] ?? 'XXXXXX'); ?></span></p>
                    </div>
                    <?php if ($viewData['isDemo']): ?>
                    <button onclick="simulateJoins()" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-play"></i> Simulate Joins
                    </button>
                    <?php else: ?>
                    <button onclick="copyInviteCode()" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-copy"></i> Copy Invite Code
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="card p-5">
                    <div class="grid grid-cols-[auto_1fr_auto] gap-4 text-xs font-semibold text-muted-ui font-mono-ui border-b border-ui pb-2">
                        <span class="w-8"></span><span>Student Info</span><span>Status</span>
                    </div>
                    <div id="live-roster-list" class="flex flex-col mt-2">
                        <div class="py-6 text-center text-muted-ui text-sm italic" id="empty-roster-state">
                            Waiting for students to join via code...
                        </div>
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
        