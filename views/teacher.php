<!-- ============================================================ -->
<!-- TEACHER — Project Groups, Evaluation Marks & Classroom      -->
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
    </div>

    <!-- TAB: Evaluation Marks -->
    <div id="tab-marks" class="view-pane flex-1 flex flex-col hidden">
        <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="text-2xl font-head font-semibold">Evaluation Marks</h2>
                <p class="text-muted-ui text-sm mt-1">
                    50 Report / 25 Presentation / 25 Q&A (100 Total)
                </p>
            </div>
            <div class="flex gap-3">
                <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>&format=print" target="_blank" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5" style="color: var(--accent); border-color: var(--accent);">
                    <i class="fas fa-print"></i> Print Sheet
                </a>
                <a href="export_marks.php?classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>&format=csv" class="btn-ui px-3.5 py-1.5 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1.5" style="background: var(--accent); color: var(--bg);">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="card flex-1 flex flex-col overflow-hidden">
            <div class="overflow-x-auto">
                <div class="min-w-[700px]">
                    <div class="grid grid-cols-[1.5fr_110px_80px_90px_80px_90px_auto] gap-3 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui items-center">
                        <span>Project Group</span>
                        <span>Guide</span>
                        <span class="text-center">Report (/50)</span>
                        <span class="text-center">Pres (/25)</span>
                        <span class="text-center">Q&A (/25)</span>
                        <span class="text-center">Total (/100)</span>
                        <span class="text-right">Action</span>
                    </div>
                    <div class="divide-y divide-ui">
                        <?php if (empty($viewData['projectGroups'])): ?>
                            <div class="py-12 text-center text-muted-ui text-sm">
                                <i class="fas fa-clipboard-check text-2xl text-muted-ui/50 mb-2"></i>
                                <p class="font-medium">No project groups created yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($viewData['projectGroups'] as $pg): ?>
                                <?php
                                $m = $pg['marks'] ?? ['report_marks' => null, 'presentation_marks' => null, 'qa_marks' => null, 'total_marks' => null];
                                ?>
                                <div class="grid grid-cols-[1.5fr_110px_80px_90px_80px_90px_auto] gap-3 px-5 py-3.5 items-center hover-overlay-subtle transition">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-sm text-text block truncate"><?php echo e($pg['name']); ?></span>
                                        <span class="text-xs text-muted-ui font-mono-ui"><?php echo count($pg['member_names'] ?? []); ?> member(s)</span>
                                    </div>
                                    <div class="text-xs text-muted-ui truncate">
                                        <?php echo !empty($pg['mentor_name']) ? e($pg['mentor_name']) : '<span class="italic">Unassigned</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs font-semibold">
                                        <?php echo ($m['report_marks'] ?? null) !== null ? number_format((float)$m['report_marks'], 1) : '<span class="text-muted-ui font-normal">-</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs">
                                        <?php echo ($m['presentation_marks'] ?? null) !== null ? number_format((float)$m['presentation_marks'], 1) : '<span class="text-muted-ui font-normal">-</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs">
                                        <?php echo ($m['qa_marks'] ?? null) !== null ? number_format((float)$m['qa_marks'], 1) : '<span class="text-muted-ui font-normal">-</span>'; ?>
                                    </div>
                                    <div class="text-center font-mono-ui text-xs font-bold text-accent">
                                        <?php echo ($m['total_marks'] ?? null) !== null ? number_format((float)$m['total_marks'], 1) : '<span class="text-muted-ui font-normal">-</span>'; ?>
                                    </div>
                                    <div class="text-right">
                                        <a href="dashboard.php?project_id=<?php echo $pg['id']; ?>&classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>" class="btn-ui px-3 py-1 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">
                                            Evaluate &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: Project Groups (Ledger) -->
    <div id="tab-groups" class="view-pane flex-1 flex flex-col">
        <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="text-2xl font-head font-semibold">Project Groups</h2>
                <p class="text-muted-ui text-sm mt-1"><?php echo $totalGroups; ?> group(s) in this classroom</p>
            </div>
        </div>

        <?php if (empty($viewData['projectGroups'])): ?>
        <div class="card p-10 text-center flex-1 flex flex-col items-center justify-center border-dashed">
            <i class="fas fa-users text-4xl mb-4 text-muted-ui opacity-50"></i>
            <h3 class="text-lg font-bold mb-2">No project groups yet</h3>
            <p class="text-muted-ui text-sm">Students haven't created teams yet. Share the invite code to get started.</p>
        </div>
        <?php else: ?>
        <div class="card flex-1 flex flex-col overflow-hidden">
            <div class="overflow-x-auto">
                <div class="min-w-[650px]">
                    <div class="grid grid-cols-[1.5fr_110px_80px_90px_90px_auto] gap-3 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui items-center">
                        <span>Team</span>
                        <span>Guide</span>
                        <span class="text-center">Members</span>
                        <span class="text-center">Meetings</span>
                        <span class="text-center">Attendance</span>
                        <span class="text-right">Action</span>
                    </div>
                    <div class="divide-y divide-ui">
                        <?php foreach ($viewData['projectGroups'] as $pg): ?>
                        <?php $h = $pg['health'] ?? ['meetings_held' => 0, 'avg_attendance_pct' => 0, 'has_shortage' => false]; ?>
                        <div class="grid grid-cols-[1.5fr_110px_80px_90px_90px_auto] gap-3 px-5 py-3.5 items-center hover-overlay-subtle transition">
                            <div class="min-w-0">
                                <span class="font-semibold text-sm text-text block truncate"><?php echo e($pg['name']); ?></span>
                                <span class="text-[11px] text-muted-ui"><?php echo e(implode(', ', $pg['member_names'] ?? [])); ?></span>
                            </div>
                            <div class="text-xs text-muted-ui truncate">
                                <?php echo !empty($pg['mentor_name']) ? e($pg['mentor_name']) : '<span class="italic">Unassigned</span>'; ?>
                            </div>
                            <div class="text-center font-mono-ui text-xs font-semibold"><?php echo count($pg['member_names'] ?? []); ?></div>
                            <div class="text-center font-mono-ui text-xs"><?php echo (int)$h['meetings_held']; ?></div>
                            <div class="text-center">
                                <?php if ((int)$h['meetings_held'] > 0): ?>
                                    <span class="font-mono-ui text-xs font-semibold <?php echo $h['has_shortage'] ? 'text-status-red' : 'text-status-green'; ?>">
                                        <?php echo number_format((float)$h['avg_attendance_pct'], 0); ?>%
                                    </span>
                                    <?php if ($h['has_shortage']): ?>
                                        <span class="block text-[9px] text-status-red font-bold uppercase">Shortage</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted-ui text-xs">-</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right flex gap-2 justify-end">
                                <?php if ($viewData['isCoordinator']): ?>
                                <form method="POST" action="assign_mentor.php" class="inline">
                                    <input type="hidden" name="project_id" value="<?php echo $pg['id']; ?>">
                                    <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                                    <select name="mentor_id" onchange="this.form.submit()" class="form-input text-xs py-1 px-2 bg-panel w-24">
                                        <option value="">Guide...</option>
                                        <?php foreach ($viewData['availableMentors'] ?? [] as $mentor): ?>
                                        <option value="<?php echo $mentor['id']; ?>" <?php echo ((int)($pg['mentor_id'] ?? 0) === (int)$mentor['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($mentor['username']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php endif; ?>
                                <a href="dashboard.php?project_id=<?php echo $pg['id']; ?>&classroom_id=<?php echo urlencode((string)$viewData['classroom_id']); ?>" class="btn-ui px-3 py-1 text-xs font-semibold" style="color: var(--accent); border-color: var(--accent);">
                                    View &rarr;
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: Classroom Roster -->
    <div id="tab-roster" class="view-pane flex-1 flex flex-col hidden">
        <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="text-2xl font-head font-semibold">Classroom</h2>
                <p class="text-muted-ui text-sm mt-1">
                    Invite Code: <span class="font-mono-ui font-bold text-accent"><?php echo e($viewData['inviteCode']); ?></span>
                </p>
            </div>
            <?php if ($viewData['isCoordinator']): ?>
            <form method="POST" action="add_mentor_to_classroom.php" class="flex items-center gap-2">
                <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id']); ?>">
                <input type="text" name="mentor_username" class="form-input text-xs py-1.5 px-3" placeholder="Add guide by username" required>
                <button type="submit" class="btn-ui px-3 py-1.5 text-xs font-semibold" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-user-plus mr-1"></i> Add Guide
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card flex-1 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-overlay-subtle border-b border-ui text-muted-ui font-mono-ui">
                            <th class="p-3">Name</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">USN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ui">
                        <?php foreach ($viewData['classroomRoster'] ?? [] as $r): ?>
                        <tr class="hover-overlay-subtle">
                            <td class="p-3 font-semibold"><?php echo e($r['username']); ?></td>
                            <td class="p-3">
                                <?php if ($r['role'] === 'Admin'): ?>
                                    <span class="badge badge-accent-2 text-[10px]">Admin / Guide</span>
                                <?php else: ?>
                                    <span class="text-muted-ui">Student</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono-ui text-muted-ui"><?php echo e($r['usn'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
