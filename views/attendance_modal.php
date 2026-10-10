<!-- views/attendance_modal.php - 10-Second Fast Batch Attendance Modal -->
<div id="attendanceModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative max-w-xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-300 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 bg-indigo-800 text-white">
            <div>
                <span id="attModalWeekBadge" class="text-[11px] font-mono uppercase tracking-wider bg-indigo-600/60 px-2 py-0.5 rounded text-indigo-200">
                    Week Review
                </span>
                <h3 id="attModalTitle" class="text-base font-bold mt-1">Saturday Guide Review & Attendance</h3>
            </div>
            <button onclick="closeAttendanceModal()" class="text-indigo-300 hover:text-white p-1 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="attendanceForm" action="save_meeting_attendance.php" method="POST" class="p-6 space-y-5 overflow-y-auto">
            <input type="hidden" name="meeting_id" id="attMeetingId" value="">

            <!-- Meeting Status Selector -->
            <div class="grid grid-cols-3 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                <label class="flex items-center justify-center py-2 px-3 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow-sm">
                    <input type="radio" name="status" value="held" id="statusHeld" class="sr-only" checked>
                    <span>Held & Evaluated</span>
                </label>
                <label class="flex items-center justify-center py-2 px-3 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white has-[:checked]:text-amber-700 has-[:checked]:shadow-sm">
                    <input type="radio" name="status" value="rescheduled" id="statusRescheduled" class="sr-only">
                    <span>Rescheduled</span>
                </label>
                <label class="flex items-center justify-center py-2 px-3 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white has-[:checked]:text-slate-700 has-[:checked]:shadow-sm">
                    <input type="radio" name="status" value="holiday" id="statusHoliday" class="sr-only">
                    <span>Institute Holiday</span>
                </label>
            </div>

            <!-- Team Members 10-Second Batch Attendance List -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700">Student Team Attendance</label>
                    <button type="button" onclick="markAllPresent()" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold underline">
                        Mark All Present
                    </button>
                </div>

                <div id="attStudentsContainer" class="space-y-2">
                    <!-- Populated dynamically via openAttendanceModal() -->
                </div>
            </div>

            <!-- Lock Warning Banner for Concluded/Past Weeks -->
            <div id="attLockWarningBanner" class="p-3 bg-slate-900 text-white rounded-xl text-xs flex items-center space-x-2 hidden">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <div>
                    <span class="font-bold text-amber-400">Locked Review Record:</span>
                    <span class="text-slate-300"> This Saturday review has concluded. Revisions to remarks or attendance require mandatory audit justification.</span>
                </div>
            </div>

            <!-- Guide Feedback -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Guide Remarks & Technical Observations</label>
                <textarea name="guide_feedback" id="attGuideFeedback" rows="3" placeholder="Enter review remarks, recommendations or design critique for this week..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Retroactive Audit Justification (Mandatory for concluded/past weeks) -->
            <div id="attAuditContainer" class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs hidden">
                <label class="block font-bold text-amber-900 mb-1 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Audit Trail: Mandatory Modification Justification <span class="text-red-600 font-bold">*</span>
                </label>
                <input type="text" name="audit_reason" id="attAuditReason" placeholder="State formal reason for modifying concluded/past record" class="w-full px-3 py-2 text-xs border border-amber-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                <p class="text-[10px] text-amber-700 mt-1">Immutably recorded in the university attendance change log.</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeAttendanceModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" id="attSubmitBtn" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-md transition-colors">
                    Save Review & Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAttendanceModal(data) {
    document.getElementById('attMeetingId').value = data.meeting_id;
    document.getElementById('attModalWeekBadge').textContent = 'Week ' + data.week_number + ' • ' + data.meeting_date;
    document.getElementById('attModalTitle').textContent = data.project_title;
    document.getElementById('attGuideFeedback').value = data.guide_feedback || '';

    // Handle locked/past week UI
    const isLocked = !!data.is_locked;
    const lockBanner = document.getElementById('attLockWarningBanner');
    const auditContainer = document.getElementById('attAuditContainer');
    const auditInput = document.getElementById('attAuditReason');
    const submitBtn = document.getElementById('attSubmitBtn');

    if (isLocked) {
        lockBanner.classList.remove('hidden');
        auditContainer.classList.remove('hidden');
        auditInput.required = true;
        submitBtn.textContent = 'Submit Revision with Audit Reason';
        submitBtn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl shadow-md transition-colors';
    } else {
        lockBanner.classList.add('hidden');
        auditContainer.classList.add('hidden');
        auditInput.required = false;
        auditInput.value = '';
        submitBtn.textContent = 'Save Review & Attendance';
        submitBtn.className = 'px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-md transition-colors';
    }

    // Status radio
    if (data.status === 'rescheduled') document.getElementById('statusRescheduled').checked = true;
    else if (data.status === 'holiday') document.getElementById('statusHoliday').checked = true;
    else document.getElementById('statusHeld').checked = true;

    // Student list container
    const container = document.getElementById('attStudentsContainer');
    container.innerHTML = '';

    data.members.forEach(m => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs';

        const info = document.createElement('div');
        info.innerHTML = `<span class="font-bold text-slate-800">${m.name}</span> <span class="font-mono text-slate-500 ml-1">(${m.identifier})</span> ${m.is_leader ? '<span class="text-[10px] text-indigo-700 bg-indigo-50 border border-indigo-200 px-1 rounded ml-1 font-semibold">Leader</span>' : ''}`;

        const toggle = document.createElement('div');
        toggle.className = 'flex items-center space-x-1.5 font-semibold text-[11px]';

        const currentAtt = m.current_status || 'present';

        toggle.innerHTML = `
            <label class="cursor-pointer">
                <input type="radio" name="attendance[${m.id}]" value="present" ${currentAtt === 'present' ? 'checked' : ''} class="sr-only peer">
                <span class="px-2.5 py-1 rounded-lg border border-slate-200 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 text-slate-600 transition-colors">P</span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="attendance[${m.id}]" value="absent" ${currentAtt === 'absent' ? 'checked' : ''} class="sr-only peer">
                <span class="px-2.5 py-1 rounded-lg border border-slate-200 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 text-slate-600 transition-colors">A</span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="attendance[${m.id}]" value="excused" ${currentAtt === 'excused' ? 'checked' : ''} class="sr-only peer">
                <span class="px-2.5 py-1 rounded-lg border border-slate-200 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 text-slate-600 transition-colors">E</span>
            </label>
        `;

        row.appendChild(info);
        row.appendChild(toggle);
        container.appendChild(row);
    });

    document.getElementById('attendanceModal').classList.remove('hidden');
}

function markAllPresent() {
    const radios = document.querySelectorAll('#attStudentsContainer input[value="present"]');
    radios.forEach(r => r.checked = true);
}

function closeAttendanceModal() {
    document.getElementById('attendanceModal').classList.add('hidden');
}
</script>
