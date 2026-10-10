<!-- views/attendance_modal.php - weekly review status, attendance and guide remarks -->
<div id="attendanceModal" class="modal">
    <div class="w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden rounded-lg bg-white">
        <div class="flex items-start justify-between px-5 py-4 border-b border-zinc-200">
            <div>
                <p id="attWeek" class="text-sm text-zinc-500"></p>
                <h3 id="attTitle" class="font-semibold"></h3>
            </div>
            <button type="button" onclick="closeModal('attendanceModal')" class="text-zinc-400 hover:text-zinc-800 text-2xl leading-none" aria-label="Close">&times;</button>
        </div>

        <form action="save_meeting_attendance.php" method="POST" class="overflow-y-auto p-5 space-y-5">
            <input type="hidden" name="meeting_id" id="attMeetingId">

            <!-- Review status -->
            <div class="grid grid-cols-3 gap-1 rounded-md bg-zinc-100 p-1 text-sm font-medium">
                <?php foreach (['held' => 'Held', 'rescheduled' => 'Rescheduled', 'holiday' => 'Holiday'] as $value => $label): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="status" value="<?= $value ?>" class="peer sr-only">
                        <span class="block rounded py-1.5 text-center text-zinc-600 peer-checked:bg-white peer-checked:text-teal-800 peer-checked:shadow-sm"><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Attendance -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="label !mb-0">Attendance</span>
                    <button type="button" onclick="markAllPresent()" class="text-sm text-teal-700 hover:underline">Mark all present</button>
                </div>
                <div id="attMembers" class="divide-y divide-zinc-100 border border-zinc-200 rounded-md"></div>
            </div>

            <!-- Remarks -->
            <div>
                <label class="label" for="attFeedback">Guide remarks</label>
                <textarea name="guide_feedback" id="attFeedback" rows="3" placeholder="Review remarks and recommendations for this week" class="field"></textarea>
            </div>

            <!-- Only shown when editing a concluded review -->
            <div id="attAudit" class="hidden rounded-md border border-amber-300 bg-amber-50 p-3">
                <label class="label text-amber-900" for="attAuditReason">This review has concluded. Why are you changing it? <span class="text-red-600">*</span></label>
                <input type="text" name="audit_reason" id="attAuditReason" class="field">
                <p class="mt-1 text-xs text-amber-800">Recorded in the attendance change log.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4">
                <button type="button" onclick="closeModal('attendanceModal')" class="btn">Cancel</button>
                <button type="submit" id="attSubmit" class="btn btn-primary">Save review</button>
            </div>
        </form>
    </div>
</div>

<script>
// project: entry from projectData; meetingId: key in project.meetings
function openAttendanceModal(project, meetingId) {
    const meeting = project.meetings[meetingId];
    if (!meeting) return;

    document.getElementById('attMeetingId').value = meetingId;
    document.getElementById('attWeek').textContent = 'Week ' + meeting.week_number + ' · ' + meeting.meeting_date;
    document.getElementById('attTitle').textContent = project.title;
    document.getElementById('attFeedback').value = meeting.guide_feedback || '';

    document.querySelectorAll('#attendanceModal input[name="status"]').forEach(r => {
        r.checked = r.value === (meeting.status === 'rescheduled' || meeting.status === 'holiday' ? meeting.status : 'held');
    });

    const locked = !!meeting.is_locked;
    document.getElementById('attAudit').classList.toggle('hidden', !locked);
    document.getElementById('attAuditReason').required = locked;
    document.getElementById('attAuditReason').value = '';
    document.getElementById('attSubmit').textContent = locked ? 'Save revision' : 'Save review';

    document.getElementById('attMembers').innerHTML = project.members.map(m => {
        const current = meeting.attendance[m.id] || 'present';
        const options = [['present', 'Present', 'peer-checked:bg-emerald-600 peer-checked:border-emerald-600'],
                         ['absent',  'Absent',  'peer-checked:bg-red-600 peer-checked:border-red-600'],
                         ['excused', 'Excused', 'peer-checked:bg-amber-500 peer-checked:border-amber-500']]
            .map(([value, label, on]) => `
                <label class="cursor-pointer">
                    <input type="radio" name="attendance[${m.id}]" value="${value}" ${current === value ? 'checked' : ''} class="peer sr-only">
                    <span class="block rounded border border-zinc-300 px-2 py-1 text-xs text-zinc-600 peer-checked:text-white ${on}">${label}</span>
                </label>`).join('');
        return `
            <div class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                <span>${esc(m.name)} <span class="text-zinc-500">(${esc(m.identifier)})</span>${m.is_leader ? ' <span class="tag tag-info">Leader</span>' : ''}</span>
                <div class="flex gap-1">${options}</div>
            </div>`;
    }).join('');

    openModal('attendanceModal');
}

function markAllPresent() {
    document.querySelectorAll('#attMembers input[value="present"]').forEach(r => r.checked = true);
}
</script>
