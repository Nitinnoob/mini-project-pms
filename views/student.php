<!-- ============================================================ -->
        <!-- STUDENT — Workbench: kanban / calendar                        -->
        <!-- ============================================================ -->
        <?php $isCalendar = ($viewData['activeBoardView'] ?? 'kanban') === 'calendar'; ?>
        <div class="<?php echo $viewData['isNewlyCreated'] ? 'col-span-4' : 'col-span-3'; ?> flex flex-col h-full">
            <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                <div class="flex items-center gap-1">
                    <button id="toggleKanbanBtn" class="tab-btn px-3 py-2 <?php echo !$isCalendar ? 'active' : ''; ?>">board.kanban</button>
                    <button id="toggleCalendarBtn" class="tab-btn px-3 py-2 <?php echo $isCalendar ? 'active' : ''; ?>">calendar.month</button>
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

            <?php require __DIR__ . '/partials/board.php'; ?>

        


</div>


