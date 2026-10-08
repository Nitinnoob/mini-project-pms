<!-- ============================================================ -->
        <!-- STUDENT — Workbench: board / phases (read-only logs)         -->
        <!-- ============================================================ -->
        <?php $isCalendar = ($viewData['activeBoardView'] ?? 'kanban') === 'calendar'; ?>
        <div class="<?php echo $viewData['isNewlyCreated'] ? 'col-span-4' : 'col-span-3'; ?> flex flex-col h-full gap-4">

            <?php require __DIR__ . '/partials/issues_panel.php'; ?>

            <div class="flex justify-between items-end flex-wrap gap-3">
                <div class="flex items-center gap-1">
                    <button id="btn-tab-board" class="tab-btn px-3 py-2 <?php echo !$isCalendar ? 'active' : ''; ?>" onclick="switchStudentTab('board')">Board</button>
                    <button id="btn-tab-logs" class="tab-btn px-3 py-2 <?php echo $isCalendar ? 'active' : ''; ?>" onclick="switchStudentTab('logs')">Phases &amp; Logs</button>
                </div>
            </div>

            <!-- TAB: Board / Calendar -->
            <div id="student-tab-board" class="view-pane flex-1 flex flex-col <?php echo $isCalendar ? 'hidden' : ''; ?>">
                <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                    <div class="flex items-center gap-1">
                        <button id="toggleKanbanBtn" class="tab-btn px-3 py-2 <?php echo !$isCalendar ? 'active' : ''; ?>">Board</button>
                        <button id="toggleCalendarBtn" class="tab-btn px-3 py-2 <?php echo $isCalendar ? 'active' : ''; ?>">Calendar</button>
                    </div>
                    <div class="flex gap-2">
                        <button data-modal-target="#addTaskModal" onclick="document.getElementById('addTaskModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent); color: var(--bg);">
                            <i class="fas fa-plus me-2"></i>Add Task
                        </button>
                        <button data-modal-target="#reportModal" onclick="document.getElementById('reportModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                            <i class="fas fa-file-word me-2"></i>Generate Report Assistant
                        </button>
                        <button data-modal-target="#escalationModal" onclick="document.getElementById('escalationModal').classList.remove('hidden')" class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                        </button>
                    </div>
                </div>

                <?php require __DIR__ . '/partials/board.php'; ?>
            </div>

            <!-- TAB: Phases & Weekly Logs (read-only for members) -->
            <div id="student-tab-logs" class="view-pane flex-1 flex flex-col <?php echo $isCalendar ? '' : 'hidden'; ?>">
                <?php require __DIR__ . '/partials/weekly_logs.php'; ?>
            </div>

        </div>

<script>
function switchStudentTab(tab) {
    var showLogs = (tab === 'logs');
    document.getElementById('student-tab-board').classList.toggle('hidden', showLogs);
    document.getElementById('student-tab-logs').classList.toggle('hidden', !showLogs);
    document.getElementById('btn-tab-board').classList.toggle('active', !showLogs);
    document.getElementById('btn-tab-logs').classList.toggle('active', showLogs);
}
</script>