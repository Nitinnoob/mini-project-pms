<!-- Fast-Entry Meeting Attendance Modal (Saturday Guide Review Engine) -->
<div id="attendanceModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="card w-full max-w-xl p-6 relative my-8 border border-ui shadow-2xl bg-panel">
        <button type="button" onclick="closeAttendanceModal()" class="absolute top-4 right-4 text-muted-ui hover:text-danger transition p-2" aria-label="Close modal">
            <i class="fas fa-times text-xl"></i>
        </button>

        <div class="mb-4 pr-8">
            <div class="flex items-center gap-2">
                <span class="badge" style="background: var(--accent-2); color: var(--bg);">Saturday Guide Review</span>
                <span class="text-xs font-mono-ui text-muted-ui" id="attModalWeekBadge">Week ?</span>
            </div>
            <h3 class="font-head font-semibold text-2xl mt-1.5" id="attModalTitle">Mark Meeting Attendance</h3>
            <p class="text-xs text-muted-ui font-mono-ui mt-0.5" id="attModalDateDisplay"></p>
        </div>

        <form id="attendanceModalForm" method="POST" action="save_meeting_attendance.php" class="space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="meeting_id" id="attModalMeetingId" value="">
            <input type="hidden" name="project_id" value="<?php echo e($viewData['myProjectId'] ?? ''); ?>">
            <input type="hidden" name="classroom_id" value="<?php echo e($viewData['classroom_id'] ?? ''); ?>">

            <!-- Meeting Status Selector -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-ui mb-2">Meeting Status</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="meetingStatusButtons">
                    <label class="cursor-pointer">
                        <input type="radio" name="meeting_status" value="held" class="sr-only peer" checked>
                        <div class="p-2.5 rounded border border-ui text-center text-xs font-semibold peer-checked:border-emerald-500 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-400 hover-overlay-subtle transition flex flex-col items-center gap-1">
                            <i class="fas fa-check-circle text-sm text-emerald-400"></i>
                            <span>Held</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="meeting_status" value="scheduled" class="sr-only peer">
                        <div class="p-2.5 rounded border border-ui text-center text-xs font-semibold peer-checked:border-zinc-400 peer-checked:bg-zinc-500/15 peer-checked:text-zinc-300 hover-overlay-subtle transition flex flex-col items-center gap-1">
                            <i class="fas fa-calendar-alt text-sm text-muted-ui"></i>
                            <span>Scheduled</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="meeting_status" value="rescheduled" class="sr-only peer">
                        <div class="p-2.5 rounded border border-ui text-center text-xs font-semibold peer-checked:border-sky-500 peer-checked:bg-sky-500/15 peer-checked:text-sky-400 hover-overlay-subtle transition flex flex-col items-center gap-1">
                            <i class="fas fa-redo-alt text-sm text-sky-400"></i>
                            <span>Rescheduled</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="meeting_status" value="holiday" class="sr-only peer">
                        <div class="p-2.5 rounded border border-ui text-center text-xs font-semibold peer-checked:border-amber-500 peer-checked:bg-amber-500/15 peer-checked:text-amber-400 hover-overlay-subtle transition flex flex-col items-center gap-1">
                            <i class="fas fa-umbrella-beach text-sm text-amber-400"></i>
                            <span>Holiday</span>
                        </div>
                    </label>
                </div>
                <p id="holidayNotice" class="text-[11px] text-muted-ui mt-1.5 hidden flex items-center gap-1">
                    <i class="fas fa-info-circle text-amber-400"></i>
                    <span>No student absences are counted for Holiday or Rescheduled meetings.</span>
                </p>
            </div>

            <!-- Fast-Entry Controls Header -->
            <div class="flex justify-between items-center pt-2 border-t border-ui">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-ui">Team Members</span>
                    <span class="text-[11px] text-muted-ui ml-1.5" id="memberCountBadge"></span>
                </div>
                <button type="button" onclick="markAllMembers('present')" class="btn-ui px-2.5 py-1 text-xs font-semibold hover-overlay-medium transition flex items-center gap-1 text-emerald-400 border-emerald-500/40 hover:border-emerald-500">
                    <i class="fas fa-bolt"></i> Mark All Present
                </button>
            </div>

            <!-- Team Members Attendance List -->
            <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1" id="attMemberList">
                <?php foreach ($viewData['actualTeamRoster'] ?? [] as $m): ?>
                <div class="flex items-center justify-between p-3 rounded bg-raised border border-ui att-member-row" data-user-id="<?php echo (int)$m['id']; ?>">
                    <div class="flex items-center gap-3 min-w-0 pr-2">
                        <div class="w-8 h-8 rounded-full flex-none flex items-center justify-center font-bold text-xs bg-overlay-strong text-muted-ui border border-ui">
                            <?php echo strtoupper(substr($m['username'], 0, 1)); ?>
                        </div>
                        <div class="truncate">
                            <div class="font-semibold text-sm truncate flex items-center gap-1.5">
                                <span><?php echo e($m['username']); ?></span>
                                <?php if (!empty($m['is_leader'])): ?>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] uppercase font-bold bg-overlay-medium text-accent">Leader</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[11px] text-muted-ui font-mono-ui prior-status-badge">Not marked yet</span>
                        </div>
                    </div>

                    <!-- 3-Choice Segmented Controls -->
                    <div class="flex-none flex rounded border border-ui overflow-hidden p-0.5 bg-panel text-xs font-semibold">
                        <button type="button" onclick="setMemberStatus(<?php echo (int)$m['id']; ?>, 'present')" class="att-status-btn px-2.5 py-1 rounded transition text-muted-ui hover:text-white" data-status="present">
                            <i class="fas fa-check mr-1 text-[10px]"></i>Present
                        </button>
                        <button type="button" onclick="setMemberStatus(<?php echo (int)$m['id']; ?>, 'absent')" class="att-status-btn px-2.5 py-1 rounded transition text-muted-ui hover:text-white" data-status="absent">
                            <i class="fas fa-times mr-1 text-[10px]"></i>Absent
                        </button>
                        <button type="button" onclick="setMemberStatus(<?php echo (int)$m['id']; ?>, 'excused')" class="att-status-btn px-2.5 py-1 rounded transition text-muted-ui hover:text-white" data-status="excused">
                            <i class="fas fa-shield-alt mr-1 text-[10px]"></i>Excused
                        </button>
                    </div>

                    <input type="hidden" name="attendance[<?php echo (int)$m['id']; ?>]" id="att_input_<?php echo (int)$m['id']; ?>" value="present">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Mandatory Edit Reason (Shown if modifying saved records) -->
            <div id="attReasonBox" class="p-3 rounded bg-amber-500/10 border border-amber-500/30 hidden">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-300 mb-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Attendance Modification Reason (Required for Audit Trail) *</span>
                </div>
                <p class="text-[11px] text-muted-ui mb-2">You are editing a previously saved attendance record. Academic review guidelines require a non-empty justification.</p>
                <input type="text" name="reason" id="attReasonInput" class="form-input text-xs" placeholder="e.g. Student submitted university medical certificate / Lab makeup session...">
            </div>

            <!-- Audit Trail / History Panel -->
            <div id="attAuditTrailBox" class="pt-3 border-t border-ui hidden">
                <details class="text-xs">
                    <summary class="cursor-pointer text-muted-ui hover:text-accent font-semibold flex items-center gap-1.5 select-none">
                        <i class="fas fa-history text-accent"></i>
                        <span>View Attendance Modification History (<span id="attAuditCount">0</span>)</span>
                    </summary>
                    <div class="mt-2.5 space-y-2 max-h-36 overflow-y-auto pr-1" id="attAuditList">
                        <!-- Filled dynamically -->
                    </div>
                </details>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-ui">
                <button type="button" onclick="closeAttendanceModal()" class="px-4 py-2 text-xs font-semibold text-muted-ui hover:text-white transition">Cancel</button>
                <button type="submit" id="saveAttSubmitBtn" class="btn-ui px-5 py-2 text-xs font-semibold shadow-lg" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-save me-1.5"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentMeetingInitialAttendance = {}; // user_id => status

function openAttendanceModal(meetingData) {
    if (!meetingData) return;

    const modal = document.getElementById('attendanceModal');
    const form = document.getElementById('attendanceModalForm');
    form.reset();

    // Populate header info
    document.getElementById('attModalMeetingId').value = meetingData.id || '';
    document.getElementById('attModalWeekBadge').innerText = 'Week ' + (meetingData.week_number || '');
    document.getElementById('attModalTitle').innerText = 'Week ' + (meetingData.week_number || '') + ' Attendance';
    
    let dateStr = meetingData.meeting_date || '';
    if (dateStr) {
        try {
            const d = new Date(dateStr + 'T00:00:00');
            dateStr = d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' });
        } catch(e) {}
    }
    document.getElementById('attModalDateDisplay').innerText = dateStr ? 'Saturday Meeting &middot; ' + dateStr : '';

    // Meeting status
    const mStatus = meetingData.status || 'held';
    const statusRadio = form.querySelector('input[name="meeting_status"][value="' + mStatus + '"]');
    if (statusRadio) {
        statusRadio.checked = true;
    } else {
        const defaultRadio = form.querySelector('input[name="meeting_status"][value="held"]');
        if (defaultRadio) defaultRadio.checked = true;
    }
    updateHolidayNotice(mStatus);

    // Initialize attendance map
    currentMeetingInitialAttendance = {};
    if (Array.isArray(meetingData.attendance)) {
        meetingData.attendance.forEach(a => {
            currentMeetingInitialAttendance[parseInt(a.user_id)] = a.status;
        });
    }

    // Set buttons for each member
    const rows = document.querySelectorAll('.att-member-row');
    rows.forEach(row => {
        const uid = parseInt(row.getAttribute('data-user-id'));
        const savedStatus = currentMeetingInitialAttendance[uid] || null;
        const initialChoice = savedStatus || 'present';

        const badge = row.querySelector('.prior-status-badge');
        if (badge) {
            if (savedStatus) {
                badge.innerText = 'Saved: ' + savedStatus.toUpperCase();
                badge.className = 'text-[11px] font-mono-ui ' + 
                    (savedStatus === 'present' ? 'text-emerald-400' : (savedStatus === 'excused' ? 'text-amber-400' : 'text-rose-400'));
            } else {
                badge.innerText = 'Not marked yet';
                badge.className = 'text-[11px] text-muted-ui font-mono-ui';
            }
        }

        setMemberStatus(uid, initialChoice, false);
    });

    // Audit changes
    const auditBox = document.getElementById('attAuditTrailBox');
    const auditList = document.getElementById('attAuditList');
    const auditCount = document.getElementById('attAuditCount');
    const changes = meetingData.attendance_changes || [];

    if (changes.length > 0) {
        auditBox.classList.remove('hidden');
        auditCount.innerText = changes.length;
        auditList.innerHTML = changes.map(ch => {
            const oldBadge = ch.old_status === 'present' ? '<span class="text-emerald-400 font-bold">Present</span>' : (ch.old_status === 'excused' ? '<span class="text-amber-400 font-bold">Excused</span>' : '<span class="text-rose-400 font-bold">Absent</span>');
            const newBadge = ch.new_status === 'present' ? '<span class="text-emerald-400 font-bold">Present</span>' : (ch.new_status === 'excused' ? '<span class="text-amber-400 font-bold">Excused</span>' : '<span class="text-rose-400 font-bold">Absent</span>');
            return `<div class="p-2 rounded bg-overlay-subtle border border-ui">
                <div class="flex justify-between items-center text-[10px] text-muted-ui font-mono-ui mb-1">
                    <span>${ch.changed_by_name || 'Guide'} &middot; ${ch.changed_at || ''}</span>
                    <span>${oldBadge} &rarr; ${newBadge}</span>
                </div>
                <div class="font-semibold text-xs text-white">${ch.username || 'Student'}</div>
                <div class="text-[11px] text-muted-ui mt-0.5 italic">&ldquo;${escapeHtml(ch.reason || '')}&rdquo;</div>
            </div>`;
        }).join('');
    } else {
        auditBox.classList.add('hidden');
        auditList.innerHTML = '';
        auditCount.innerText = '0';
    }

    checkModificationsAndToggleReason();
    modal.classList.remove('hidden');
}

function closeAttendanceModal() {
    document.getElementById('attendanceModal').classList.add('hidden');
}

function setMemberStatus(userId, status, triggerCheck = true) {
    const row = document.querySelector(`.att-member-row[data-user-id="${userId}"]`);
    if (!row) return;

    const input = document.getElementById('att_input_' + userId);
    if (input) input.value = status;

    const buttons = row.querySelectorAll('.att-status-btn');
    buttons.forEach(btn => {
        const btnStatus = btn.getAttribute('data-status');
        btn.classList.remove('bg-emerald-600', 'bg-rose-600', 'bg-amber-600', 'text-white', 'font-bold');
        btn.classList.add('text-muted-ui');

        if (btnStatus === status) {
            btn.classList.remove('text-muted-ui');
            btn.classList.add('text-white', 'font-bold');
            if (status === 'present') btn.classList.add('bg-emerald-600');
            else if (status === 'absent') btn.classList.add('bg-rose-600');
            else if (status === 'excused') btn.classList.add('bg-amber-600');
        }
    });

    if (triggerCheck) {
        checkModificationsAndToggleReason();
    }
}

function markAllMembers(status) {
    const rows = document.querySelectorAll('.att-member-row');
    rows.forEach(row => {
        const uid = parseInt(row.getAttribute('data-user-id'));
        setMemberStatus(uid, status, false);
    });
    checkModificationsAndToggleReason();
}

function checkModificationsAndToggleReason() {
    let hasModification = false;
    const rows = document.querySelectorAll('.att-member-row');

    rows.forEach(row => {
        const uid = parseInt(row.getAttribute('data-user-id'));
        const input = document.getElementById('att_input_' + uid);
        const currentVal = input ? input.value : 'present';
        const savedVal = currentMeetingInitialAttendance[uid];

        if (savedVal && savedVal !== currentVal) {
            hasModification = true;
        }
    });

    const reasonBox = document.getElementById('attReasonBox');
    const reasonInput = document.getElementById('attReasonInput');
    if (hasModification) {
        reasonBox.classList.remove('hidden');
        reasonInput.setAttribute('required', 'required');
    } else {
        reasonBox.classList.add('hidden');
        reasonInput.removeAttribute('required');
    }
}

function updateHolidayNotice(status) {
    const notice = document.getElementById('holidayNotice');
    if (notice) {
        if (status === 'holiday' || status === 'rescheduled') {
            notice.classList.remove('hidden');
        } else {
            notice.classList.add('hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const statusRadios = document.querySelectorAll('input[name="meeting_status"]');
    statusRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateHolidayNotice(this.value);
        });
    });

    const form = document.getElementById('attendanceModalForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const reasonBox = document.getElementById('attReasonBox');
            const reasonInput = document.getElementById('attReasonInput');
            if (!reasonBox.classList.contains('hidden')) {
                if (!reasonInput.value || !reasonInput.value.trim()) {
                    e.preventDefault();
                    reasonInput.focus();
                    alert('Please provide a mandatory reason for modifying previously saved attendance.');
                    return false;
                }
            }
        });
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
