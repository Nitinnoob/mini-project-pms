<!-- ============================================================ -->
        <!-- TEACHER — Marking desk & Live Roster                          -->
        <!-- ============================================================ -->
        <?php
        $totalGroups = count($viewData['projectGroups'] ?? []);
        ?>
        <div class="col-span-4 flex flex-col h-full">
            
            <!-- Teacher Tabs -->
            <div class="flex gap-6 border-b border-ui mb-6 flex-wrap">
                <button id="btn-tab-groups" class="tab-btn active pb-2" onclick="switchTeacherTab('groups')">Project Groups</button>
                <button id="btn-tab-roster" class="tab-btn pb-2" onclick="switchTeacherTab('roster')">Classroom</button>
                <button id="btn-tab-marks" class="tab-btn pb-2" onclick="switchTeacherTab('marks')">Evaluation Marks</button>
                <button id="btn-tab-phases" class="tab-btn pb-2" onclick="switchTeacherTab('phases')">Phases</button>
            </div>

            <!-- TAB: Evaluation Marks (CIE & Viva Marks Sheet) -->
            <div id="tab-marks" class="view-pane flex-1 flex flex-col hidden">
                <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                    <div>
                        <h2 class="text-2xl font-head font-semibold">Continuous Evaluation Marks</h2>
                        <p class="text-muted-ui text-sm mt-1">
                            50 Report / 25 Presentation / 25 Q&A (100 Total) &middot; Guide evaluations & finalization desk
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>&format=print" target="_blank" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-print"></i> Print Official Sheet
                        </a>
                        <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>&format=csv" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5" style="background: var(--accent); color: var(--bg);">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <div class="card flex-1 flex flex-col overflow-hidden">
                    <div class="grid grid-cols-[1.5fr_130px_100px_100px_110px_auto] gap-3 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui items-center">
                        <span>Project Group</span>
                        <span>Guide</span>
                        <span class="text-center">Report (/50)</span>
                        <span class="text-center">Attendance</span>
                        <span class="text-center">Evaluation</span>
                        <span class="text-right">Action</span>
                    </div>
                    <div class="flex-1 overflow-y-auto divide-y divide-ui">
                        <?php if (empty($viewData['projectGroups'])): ?>
                            <div class="py-12 text-center text-muted-ui text-sm">
                                <i class="fas fa-clipboard-check text-2xl text-muted-ui/50 mb-2"></i>
                                <p class="font-medium">No project groups created yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($viewData['projectGroups'] as $pg): ?>
                                <?php
                                $m = $pg['marks'] ?? ['report_marks' => null, 'is_finalized' => false];
                                $h = $pg['health'] ?? ['attendance_pct' => 100.0];
                                $isFin = !empty($m['is_finalized']);
                                ?>
                                <div class="grid grid-cols-[1.5fr_130px_100px_100px_110px_auto] gap-3 px-5 py-3.5 items-center hover-overlay-subtle transition">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-sm text-white block truncate"><?php echo e($pg['name']); ?></span>
                                        <span class="text-xs text-muted-ui font-mono-ui"><?php echo count($pg['member_names'] ?? []); ?> member(s)</span>
                                    </div>
                                    <div class="text-xs text-muted-ui truncate">
                                        <?php echo !empty($pg['mentor_name']) ? e($pg['mentor_name']) : '<span class="italic">Unassigned</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs font-semibold">
                                        <?php echo $m['report_marks'] !== null ? number_format((float)$m['report_marks'], 1) . ' / 50' : '<span class="text-muted-ui font-normal">-</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs">
                                        <span class="<?php echo ($h['attendance_pct'] < 75.0) ? 'text-amber-400 font-bold' : ''; ?>">
                                            <?php echo $h['attendance_pct']; ?>%
                                        </span>
                                    </div>
                                    <div class="text-center">
                                        <?php if ($isFin): ?>
                                            <span class="badge badge-green font-mono-ui text-[11px] inline-flex items-center gap-1">
                                                <i class="fas fa-lock"></i> Finalized
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-muted font-mono-ui text-[11px]">
                                                Draft
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <a href="dashboard.php?project_id=<?php echo $pg['id']; ?>&classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-3 py-1 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">
                                            Audit & Marks &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
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
                            <?php echo e(date('M j, Y', strtotime($viewData['classroomStartDate']))); ?>
                            &ndash;
                            <?php echo e(date('M j, Y', strtotime($viewData['classroomEndDate']))); ?>.
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
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                            <input type="hidden" name="week_number" value="<?php echo (int)$p['week_number']; ?>">
                                            <input type="hidden" name="action" value="rename">
                                            <input type="text" name="label" value="<?php echo e($p['label']); ?>" maxlength="120" required
                                                   class="bg-panel border border-ui rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:border-accent transition">
                                            <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">Save</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="font-semibold text-sm"><?php echo e($p['label']); ?></span>
                                    <?php endif; ?>
                                    <span class="block text-xs text-muted-ui font-mono-ui mt-1">
                                        <?php echo e(date('M j', strtotime($p['date_from'])) . ' – ' . date('M j', strtotime($p['date_to']))); ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 flex-none flex-wrap justify-end">
                                    <?php if ($p['is_current']): ?>
                                        <span class="badge" style="background: var(--accent-2); color: var(--bg);">Current</span>
                                    <?php endif; ?>

                                    <?php if (!empty($p['merged_from'])): ?>
                                        <span class="badge badge-accent" title="Weekly logs from these phases roll up into this one">
                                            + Week <?php echo e(implode(', ', $p['merged_from'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($viewData['isCoordinator'])): ?>
                                <div class="flex items-center gap-2 flex-none">
                                    <?php if ($p['is_merged']): ?>
                                        <span class="badge badge-muted">Merged into Week <?php echo (int)$p['merged_into_week']; ?></span>
                                        <form method="POST" action="manage_phase.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                            <input type="hidden" name="week_number" value="<?php echo (int)$p['week_number']; ?>">
                                            <input type="hidden" name="action" value="unmerge">
                                            <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">Unmerge</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="manage_phase.php" class="flex items-center gap-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
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
                                                        <?php echo e($target['label']); ?>
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
                        <h2 class="text-2xl font-head font-semibold"><?php echo e($viewData['classroomName'] ?? 'Classroom'); ?></h2>
                        <p class="text-muted-ui text-sm mt-1" id="ledger-stats"><?php echo $totalGroups; ?> groups</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="copyInviteCode()" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-copy"></i> Copy Invite Code
                        </button>
                    </div>
                </div>



                <div class="card flex-1 flex flex-col">
                    <div class="grid grid-cols-[1.5fr_130px_100px_90px_85px_70px_auto] gap-3 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui items-center">
                        <span>Project Group</span>
                        <span>Health</span>
                        <span class="text-center">Meetings</span>
                        <span class="text-center">Attendance</span>
                        <span class="text-center">Directives</span>
                        <span class="text-right">Team</span>
                        <span class="text-right">Audit</span>
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
                        <?php foreach ($viewData['projectGroups'] as $index => $g): ?>
                        <?php
                        $h = $g['health'] ?? [
                            'status'               => 'neutral',
                            'label'                => 'Pending',
                            'badge_class'          => 'badge-muted',
                            'dot_color'            => 'bg-muted-ui',
                            'meetings_held'        => 0,
                            'meetings_total'       => 0,
                            'meetings_past_unheld' => 0,
                            'attendance_pct'       => 100.0,
                            'open_instructions'    => 0,
                            'reasons'              => ['Pending'],
                        ];
                        ?>
                        <div class="ledger-item flex flex-col border-b border-ui last:border-b-0">
                            <div class="ledger-row grid grid-cols-[1.5fr_130px_100px_90px_85px_70px_auto] gap-3 px-5 py-3.5 items-center cursor-pointer hover-overlay-subtle transition" onclick="document.getElementById('details-<?php echo $index; ?>').classList.toggle('hidden'); document.getElementById('chevron-<?php echo $index; ?>').classList.toggle('rotate-180')">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <i class="fas fa-chevron-down text-xs text-muted-ui flex-none transition-transform duration-200" id="chevron-<?php echo $index; ?>"></i>
                                    <div class="min-w-0">
                                        <span class="font-semibold text-sm truncate block"><?php echo e($g['name']); ?></span>
                                        <span class="text-[11px] text-muted-ui truncate block">
                                            Guide: <?php echo !empty($g['mentor_name']) ? e($g['mentor_name']) : '<span class="italic">Unassigned</span>'; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Health Status Badge -->
                                <div>
                                    <span class="badge <?php echo $h['badge_class']; ?> text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5">
                                        <span class="w-2 h-2 rounded-full <?php echo $h['dot_color']; ?>"></span>
                                        <span><?php echo e($h['label']); ?></span>
                                    </span>
                                </div>

                                <!-- Meetings Progress -->
                                <div class="text-center font-mono-ui text-xs">
                                    <span class="font-semibold"><?php echo (int)$h['meetings_held']; ?></span><span class="text-muted-ui">/<?php echo (int)$h['meetings_total']; ?></span>
                                    <?php if ($h['meetings_past_unheld'] > 0): ?>
                                        <span class="block text-[10px] text-rose-400 font-semibold"><?php echo (int)$h['meetings_past_unheld']; ?> missed</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Attendance Percentage -->
                                <div class="text-center font-mono-ui text-xs">
                                    <span class="font-semibold <?php echo ($h['attendance_pct'] < 75.0) ? 'text-amber-400' : ''; ?>">
                                        <?php echo $h['attendance_pct']; ?>%
                                    </span>
                                    <?php if ($h['attendance_pct'] < 75.0): ?>
                                        <span class="block text-[10px] text-amber-400 font-semibold">Shortage</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Directives / Instructions -->
                                <div class="text-center font-mono-ui text-xs">
                                    <?php if ($h['open_instructions'] > 0): ?>
                                        <span class="badge badge-warning text-[10px] px-1.5 py-0.5"><?php echo (int)$h['open_instructions']; ?> open</span>
                                    <?php else: ?>
                                        <span class="text-emerald-400 text-xs"><i class="fas fa-check"></i> 0 open</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Team Count (Max 4) -->
                                <div class="text-right text-xs font-mono-ui text-muted-ui">
                                    <?php echo (int)$g['members']; ?> / 4
                                </div>

                                <!-- Action Arrow -->
                                <div class="text-right">
                                    <a href="dashboard.php?project_id=<?php echo $g['id']; ?>&classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" onclick="event.stopPropagation();" class="btn-ui px-2.5 py-1 text-xs text-accent hover:opacity-80 transition" title="Audit & Drilldown">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Expanded Details & Health Diagnosis -->
                            <div id="details-<?php echo $index; ?>" class="hidden px-8 py-4 bg-overlay-subtle border-t border-ui text-sm">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Health Diagnosis -->
                                    <div class="p-3 rounded border <?php echo ($h['status'] === 'critical') ? 'border-red-500/30 bg-red-950/20' : (($h['status'] === 'warning') ? 'border-amber-500/30 bg-amber-950/20' : 'border-emerald-500/30 bg-emerald-950/10'); ?>">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="w-2.5 h-2.5 rounded-full <?php echo $h['dot_color']; ?>"></span>
                                            <span class="font-head font-semibold text-xs uppercase tracking-wider">Health Status: <?php echo e($h['label']); ?></span>
                                        </div>
                                        <ul class="text-xs space-y-1 text-muted-ui">
                                            <?php foreach ($h['reasons'] as $r): ?>
                                                <li class="flex items-start gap-1.5">
                                                    <span class="text-muted-ui">&bull;</span>
                                                    <span><?php echo e($r); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="mt-3 pt-2 border-t border-ui/40 text-[11px] font-mono-ui text-muted-ui flex justify-between">
                                            <span>Held: <?php echo (int)$h['meetings_held']; ?>/<?php echo (int)$h['meetings_total']; ?></span>
                                            <span>Att: <?php echo $h['attendance_pct']; ?>%</span>
                                            <span>Open: <?php echo (int)$h['open_instructions']; ?></span>
                                        </div>
                                    </div>

                                    <!-- Description & Team -->
                                    <div class="flex flex-col justify-between">
                                        <div>
                                            <h4 class="font-semibold text-xs uppercase tracking-wider text-muted-ui mb-1">Project Description</h4>
                                            <p class="text-xs text-muted-ui mb-3"><?php echo e($g['desc'] ?? 'No description provided.'); ?></p>
                                            <h4 class="font-semibold text-xs uppercase tracking-wider text-muted-ui mb-1">Team Members</h4>
                                            <ul class="list-disc list-inside text-xs text-muted-ui">
                                                <?php foreach ($g['member_names'] ?? [] as $member): ?>
                                                    <li><?php echo e($member); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Guide & Drilldown Link -->
                                    <div class="flex flex-col justify-between items-start md:items-end">
                                        <div class="w-full md:text-right">
                                            <h4 class="font-semibold text-xs uppercase tracking-wider text-muted-ui mb-1">Assigned Guide</h4>
                                            <?php if (!empty($viewData['isCoordinator'])): ?>
                                            <form method="POST" action="assign_mentor.php" class="flex gap-2 items-center justify-start md:justify-end mb-3">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="project_id" value="<?php echo $g['id']; ?>">
                                                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                                <select name="mentor_id" class="form-input text-xs py-1 px-2" style="width: auto;">
                                                    <option value="">-- No Mentor --</option>
                                                    <?php foreach ($viewData['availableMentors'] ?? [] as $mentor): ?>
                                                        <option value="<?php echo $mentor['id']; ?>" <?php echo ($g['mentor_id'] == $mentor['id']) ? 'selected' : ''; ?>>
                                                            <?php echo e($mentor['username']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn-ui px-3 py-1 text-xs hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">Assign</button>
                                            </form>
                                            <?php else: ?>
                                            <p class="text-muted-ui text-xs mb-3">
                                                <?php echo !empty($g['mentor_name']) ? e($g['mentor_name']) : 'Unassigned'; ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-2 text-right">
                                            <span class="text-[11px] font-mono-ui text-muted-ui block mb-2">
                                                Evaluation: <?php echo !empty($g['marks']['is_finalized']) ? '<span class="text-emerald-400 font-semibold"><i class="fas fa-lock"></i> Finalized</span>' : '<span class="text-muted-ui">Draft</span>'; ?>
                                                <?php if ($g['marks']['report_marks'] !== null): ?>
                                                    &middot; Report: <strong><?php echo (float)$g['marks']['report_marks']; ?>/50</strong>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <a href="dashboard.php?project_id=<?php echo $g['id']; ?>&classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover-overlay-medium transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                                            <span>Audit & Review Meetings</span> <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
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
                        <h2 class="text-2xl font-head font-semibold"><?php echo e($viewData['classroomName'] ?? 'Classroom'); ?></h2>
                        <p class="text-muted-ui text-sm mt-1">Invite Code: <span class="font-mono-ui font-semibold px-2 py-1 bg-overlay-subtle rounded"><?php echo e($viewData['inviteCode'] ?? 'XXXXXX'); ?></span></p>
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
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
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
                                    <div class="font-semibold text-sm"><?php echo e($mentor['username']); ?></div>
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
                                        <div class="font-semibold text-sm"><?php echo e($student['username']); ?></div>
                                        <div class="text-xs text-muted-ui font-mono-ui tracking-wide"><?php echo e($student['usn'] ?? 'No USN'); ?></div>
                                    </div>
                                    <div>
                                        <?php if ($student['project_name']): ?>
                                            <span class="badge badge-green text-[10px] uppercase tracking-wider">In <?php echo e($student['project_name']); ?></span>
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
                const code = "<?php echo e($viewData['inviteCode'] ?? 'XXXXXX'); ?>";
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

