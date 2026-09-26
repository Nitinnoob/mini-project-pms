        <!-- ============================================================ -->
        <!-- STUDENT — Workbench: kanban / calendar                        -->
        <!-- ============================================================ -->
        <div class="<?php echo $viewData['isNewlyCreated'] ? 'col-span-4' : 'col-span-3'; ?> flex flex-col h-full">
            <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                <div class="flex items-center gap-1">
                    <button id="toggleKanbanBtn" class="tab-btn active px-3 py-2">board.kanban</button>
                    <button id="toggleCalendarBtn" class="tab-btn px-3 py-2">calendar.month</button>
                </div>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('addTaskModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent); color: var(--bg);">
                        <i class="fas fa-plus me-2"></i>Add Task
                    </button>
                    <button class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-magic me-2"></i>Generate Friday wrap-up
                    </button>
                    <button class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                    </button>
                </div>
            </div>

            <div id="kanban-view" class="view-pane grid grid-cols-3 gap-4 flex-1">
                <div class="card p-4 flex flex-col">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">To do <span class="bg-raised border border-ui px-2 text-xs py-0.5" style="border-radius: var(--radius);"><?php echo count($viewData['tasks']["todo"] ?? []); ?></span></h3>
                    <div id="todo-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php if ($viewData['isDemo']): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);">database</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Resolve explicit cursor syntax in Oracle schema</p>
                        </div>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">os-lab</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Compile CPU scheduling algorithms</p>
                            <div class="mt-2 text-xs text-danger font-semibold"><i class="far fa-clock me-1"></i>Due tomorrow</div>
                        </div>
                        <?php else: foreach (($viewData['tasks']['todo'] ?? []) as $task): ?>
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
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent-2);">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">In progress <span class="px-2 text-xs py-0.5" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);"><?php echo count($viewData['tasks']["inprogress"] ?? []); ?></span></h3>
                    <div id="inprogress-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php if ($viewData['isDemo']): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius); border-left: 2px solid var(--accent-2);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">docs</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Draft Phase 1 system architecture report</p>
                        </div>
                        <?php else: foreach (($viewData['tasks']['inprogress'] ?? []) as $task): ?>
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
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent);">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">Done <span class="px-2 text-xs py-0.5" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);"><?php echo count($viewData['tasks']["done"] ?? []); ?></span></h3>
                    <div id="done-list" class="flex-1 space-y-3 min-h-[200px]">
                        <?php if ($viewData['isDemo']): ?>
                        <div class="bg-raised border border-ui p-3 cursor-grab opacity-60" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui text-muted-ui" style="background: rgba(127,127,127,.15); border-radius: var(--radius);">frontend</span>
                                <i class="fas fa-check text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium line-through">Design dark mode toggle</p>
                        </div>
                        <?php else: foreach (($viewData['tasks']['done'] ?? []) as $task): ?>
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
                        <?php endforeach; endif; ?>
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
                                $bg = $t['priority'] === 'high' ? 'rgba(239,100,97,.18)' : 'rgba(69,208,195,.18)';
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

