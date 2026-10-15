<!-- ============================================================ -->
<!-- MEETING MODAL — Add/Edit Meeting (Guide Only)               -->
<!-- ============================================================ -->
<?php
$teamRoster = $viewData['actualTeamRoster'] ?? [];
?>

<div id="meetingModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
    <div class="card w-full max-w-xl max-h-[90vh] flex flex-col p-6 border-ui bg-panel shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="flex justify-between items-start border-b border-ui pb-4 mb-4">
            <div>
                <h3 class="text-lg font-head font-bold flex items-center gap-2">
                    <i class="fas fa-calendar-check text-accent"></i>
                    <span id="meetingModalTitle">Add Meeting</span>
                </h3>
                <p class="text-xs text-muted-ui mt-1">Record attendance and guidance notes for this meeting.</p>
            </div>
            <button type="button" onclick="closeMeetingModal()" class="text-muted-ui hover:text-text p-1 transition">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <form id="meetingForm" method="POST" action="save_meeting.php" class="flex-1 flex flex-col overflow-y-auto space-y-5">
            <input type="hidden" name="meeting_id" id="meetingModalId" value="">
            <input type="hidden" name="project_id" value="<?php echo (int)($viewData['myProjectId'] ?? 0); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo (int)($viewData['classroom_id'] ?? 0); ?>">

            <!-- Meeting Date -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-ui mb-2">Meeting Date</label>
                <input type="date" name="meeting_date" id="meetingModalDate" class="form-input w-full" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <!-- Guidance / Changes Requested -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-ui mb-2">Guidance / Changes Requested</label>
                <textarea name="guidance_notes" id="meetingModalNotes" class="form-input w-full h-28" placeholder="Enter guidance, feedback, or changes requested for the team..."></textarea>
            </div>

            <!-- Attendance -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-muted-ui">Attendance</label>
                    <div class="flex gap-2">
                        <button type="button" onclick="markAllAttendance('present')" class="text-[10px] px-2 py-0.5 rounded border border-ui hover-overlay-medium text-status-green font-semibold">
                            All Present
                        </button>
                        <button type="button" onclick="markAllAttendance('absent')" class="text-[10px] px-2 py-0.5 rounded border border-ui hover-overlay-medium text-status-red font-semibold">
                            All Absent
                        </button>
                    </div>
                </div>
                <div class="space-y-2" id="attendanceList">
                    <?php foreach ($teamRoster as $member): ?>
                    <div class="flex items-center justify-between p-3 rounded border border-ui bg-raised">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--accent); color: var(--bg);">
                                <?php echo strtoupper(substr($member['username'] ?? '', 0, 1)); ?>
                            </div>
                            <span class="text-sm font-semibold"><?php echo e($member['username'] ?? ''); ?></span>
                        </div>
                        <div class="flex gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="attendance[<?php echo (int)$member['id']; ?>]" value="present" class="sr-only peer" checked>
                                <span class="px-3 py-1 text-xs font-semibold rounded border border-ui peer-checked:bg-emerald-500/20 peer-checked:border-emerald-500 peer-checked:text-status-green transition">Present</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="attendance[<?php echo (int)$member['id']; ?>]" value="absent" class="sr-only peer">
                                <span class="px-3 py-1 text-xs font-semibold rounded border border-ui peer-checked:bg-red-500/20 peer-checked:border-red-500 peer-checked:text-status-red transition">Absent</span>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-ui pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeMeetingModal()" class="btn-ui px-4 py-1.5 text-xs text-muted-ui">Cancel</button>
                <button type="submit" class="btn-ui px-5 py-1.5 text-xs font-semibold" style="background: var(--accent); color: var(--bg);">
                    <i class="fas fa-save mr-1"></i> Save Meeting
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMeetingModal(meetingId, date, notes, attendance) {
    document.getElementById('meetingModalId').value = meetingId || '';
    document.getElementById('meetingModalDate').value = date || new Date().toISOString().split('T')[0];
    document.getElementById('meetingModalNotes').value = notes || '';
    document.getElementById('meetingModalTitle').textContent = meetingId ? 'Edit Meeting' : 'Add Meeting';

    // Reset all to present by default
    document.querySelectorAll('#attendanceList input[type="radio"][value="present"]').forEach(r => r.checked = true);

    // Apply saved attendance if editing
    if (attendance && typeof attendance === 'object') {
        Object.keys(attendance).forEach(uid => {
            const radio = document.querySelector('input[name="attendance[' + uid + ']"][value="' + attendance[uid] + '"]');
            if (radio) radio.checked = true;
        });
    }

    document.getElementById('meetingModal').classList.remove('hidden');
}

function closeMeetingModal() {
    document.getElementById('meetingModal').classList.add('hidden');
}

function markAllAttendance(status) {
    document.querySelectorAll('#attendanceList input[type="radio"][value="' + status + '"]').forEach(r => r.checked = true);
}
</script>
