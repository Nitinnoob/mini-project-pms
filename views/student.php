<!-- ============================================================ -->
        <!-- STUDENT — Personal Attendance & Weekly Guide Reviews          -->
        <!-- ============================================================ -->
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
        <div class="col-span-4 flex flex-col h-full gap-4">

            <?php if (!empty($pa['is_shortage'])): ?>
            <div class="p-3.5 rounded border border-red-500/40 bg-red-950/40 text-red-200 text-xs flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-exclamation-triangle text-base text-red-400"></i>
                    <div>
                        <strong>Attendance Shortage Alert:</strong> Your attendance (<?php echo $pa['percentage']; ?>%) is below the university 75% threshold. Please meet with your guide immediately.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Personal Attendance Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="card stat-tile <?php echo $attTone; ?> p-4">
                    <div class="stat-num text-3xl font-mono-ui font-bold">
                        <?php echo $pa['percentage']; ?>%
                    </div>
                    <div class="text-xs text-muted-ui mt-1 font-semibold">Personal Attendance Rate</div>
                </div>

                <div class="card p-4 flex flex-col justify-center">
                    <div class="text-xs text-muted-ui font-semibold uppercase tracking-wider mb-1">Guide Meetings Attendance</div>
                    <div class="text-xs font-mono-ui flex items-center gap-2 flex-wrap mt-0.5">
                        <span class="text-emerald-400 font-semibold"><i class="fas fa-check mr-1"></i><?php echo (int)$pa['present']; ?> Present</span>
                        <span class="text-muted-ui">&middot;</span>
                        <span class="text-rose-400 font-semibold"><i class="fas fa-times mr-1"></i><?php echo (int)$pa['absent']; ?> Absent</span>
                        <span class="text-muted-ui">&middot;</span>
                        <span class="text-amber-400 font-semibold"><i class="fas fa-shield-alt mr-1"></i><?php echo (int)$pa['excused']; ?> Excused</span>
                    </div>
                </div>

                <div class="card p-4 flex flex-col justify-center">
                    <div class="text-xs text-muted-ui font-semibold uppercase tracking-wider mb-1">Review Engine Progress</div>
                    <div class="text-sm font-semibold mt-0.5">
                        <?php echo (int)$pa['held']; ?> held <span class="text-xs text-muted-ui font-normal">of <?php echo (int)$pa['total_meetings']; ?> scheduled meetings</span>
                    </div>
                    <?php if ($pa['holiday'] > 0 || $pa['rescheduled'] > 0): ?>
                        <div class="text-[11px] text-muted-ui mt-1 font-mono-ui">
                            <?php if ($pa['holiday'] > 0): ?><span><?php echo (int)$pa['holiday']; ?> holiday</span><?php endif; ?>
                            <?php if ($pa['holiday'] > 0 && $pa['rescheduled'] > 0): ?> &middot; <?php endif; ?>
                            <?php if ($pa['rescheduled'] > 0): ?><span><?php echo (int)$pa['rescheduled']; ?> rescheduled</span><?php endif; ?>
                            <span>(no absences counted)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Classroom Milestones & Countdown -->
            <?php require __DIR__ . '/partials/milestone_widget.php'; ?>

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

            <!-- Weekly Logs & Saturday Reviews -->
            <div class="flex justify-between items-center pb-2 border-b border-ui">
                <h3 class="font-head font-semibold text-sm">Saturday Guide Review Logs</h3>
                <button data-modal-target="#reportModal" onclick="document.getElementById('reportModal').classList.remove('hidden')" class="btn-ui text-xs font-semibold px-3 py-1.5" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-file-word me-1.5"></i>Generate Report Assistant
                </button>
            </div>
            <div class="view-pane flex-1 flex flex-col">
                <?php require __DIR__ . '/partials/weekly_logs.php'; ?>
            </div>
        </div>