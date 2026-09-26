const escapeHTML = (str) => {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
};

function switchTeacherTab(tab) {
                document.getElementById('btn-tab-groups').classList.toggle('active', tab === 'groups');
                document.getElementById('btn-tab-roster').classList.toggle('active', tab === 'roster');
                document.getElementById('tab-groups').classList.toggle('hidden', tab !== 'groups');
                document.getElementById('tab-roster').classList.toggle('hidden', tab !== 'roster');
            }

            const mockStudents = [
                {name: "CHAITHRA AB", usn: "1RG24CS015"},
                {name: "KEERTHANA", usn: "1RG24CS034"},
                {name: "ANKITHA V", usn: "1RG24CS009"},
                {name: "ARCHANA NAVI", usn: "1RG24CS011"},
                {name: "Twayib", usn: "1RG24CS089"},
                {name: "Siddharth", usn: "1RG24CS112"}
            ];
            
            let simRunning = false;
            function simulateJoins() {
                if(simRunning) return;
                simRunning = true;
                const list = document.getElementById('live-roster-list');
                const emptyState = document.getElementById('empty-roster-state');
                if(emptyState) emptyState.remove();
                
                let delay = 0;
                mockStudents.forEach((student, index) => {
                    setTimeout(() => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-[auto_1fr_auto] gap-4 py-3 border-b border-ui items-center view-pane';
                        row.innerHTML = `
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent); color: #fff;">
                                ${student.name.charAt(0)}
                            </div>
                            <div>
                                <div class="font-semibold text-sm">${student.name}</div>
                                <div class="text-xs text-muted-ui font-mono-ui tracking-wide">${student.usn}</div>
                            </div>
                            <div>
                                <span class="px-2 py-1 text-[10px] font-semibold rounded uppercase tracking-wider" style="background: rgba(63, 108, 81, 0.1); color: var(--accent);">Joined</span>
                            </div>
                        `;
                        list.prepend(row);
                        
                        // Show toast
                        const toast = document.getElementById('toast');
                        if (toast) {
                            toast.querySelector('h4').innerText = "New Member Joined";
                            toast.querySelector('p').innerText = `${student.name} (${student.usn}) entered the classroom.`;
                            toast.classList.add('show');
                            setTimeout(() => toast.classList.remove('show'), 3000);
                        }
                    }, delay);
                    // Add staggered timing for realism
                    delay += 1000 + Math.random() * 1200; 
                });
            }

// Kanban drag and drop (Student layout only)
        function showToast(msg){ const t=document.getElementById('toast'); t.querySelector('p').textContent=msg;
          t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),3000); }

        const kanbanOptions = { group:'shared', animation:150,
          onEnd(evt){
            if (evt.to === evt.from) return;
            ['todo-list','inprogress-list','done-list'].forEach(id=>{
              const l=document.getElementById(id);
              if(l && l.previousElementSibling) l.previousElementSibling.querySelector('span').textContent=l.children.length;
            });
            const done = evt.to.id==='done-list';
            evt.item.classList.toggle('opacity-60', done);
            evt.item.querySelector('p')?.classList.toggle('line-through', done);
            
            const taskId = evt.item.getAttribute('data-task-id');
            const newStatus = evt.to.id.replace('-list', ''); // 'todo', 'inprogress', 'done'
            
            if (taskId) {
                const fd = new FormData();
                fd.append('task_id', taskId);
                fd.append('status', newStatus);
                
                fetch('update_task_status.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if(data.success && done) showToast('Task completed! Progress updated.');
                });
            } else {
                const bar=document.querySelector('.progress-fill');
                if (bar) {
                    const w = parseInt(bar.style.width) || 74;
                    if (done) { bar.style.width=Math.min(100,w+2)+'%'; showToast('Demo task updated.'); }
                    else if (evt.from.id==='done-list') bar.style.width=Math.max(0,w-2)+'%';
                }
            }
          }};
        ['todo-list', 'inprogress-list', 'done-list'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !window.PMS_IS_READONLY) new Sortable(el, kanbanOptions);
        });

        // Kanban / Calendar toggle (Student layout only)
        const toggleKanbanBtn = document.getElementById('toggleKanbanBtn');
        const toggleCalendarBtn = document.getElementById('toggleCalendarBtn');
        const kanbanView = document.getElementById('kanban-view');
        const calendarView = document.getElementById('calendar-view');
        if (toggleKanbanBtn && toggleCalendarBtn) {
            toggleKanbanBtn.addEventListener('click', () => {
                kanbanView.classList.remove('hidden'); calendarView.classList.add('hidden');
                toggleKanbanBtn.classList.add('active'); toggleCalendarBtn.classList.remove('active');
            });
            toggleCalendarBtn.addEventListener('click', () => {
                calendarView.classList.remove('hidden'); kanbanView.classList.add('hidden');
                toggleCalendarBtn.classList.add('active'); toggleKanbanBtn.classList.remove('active');
            });
        }

        // Leader tabs: My Board <-> Team
        function switchLeaderTab(tab) {
            const boardBtn = document.getElementById('btn-tab-board');
            const teamBtn = document.getElementById('btn-tab-team');
            const boardPane = document.getElementById('leader-tab-board');
            const teamPane = document.getElementById('leader-tab-team');
            if (!boardBtn || !teamBtn || !boardPane || !teamPane) return;
            boardBtn.classList.toggle('active', tab === 'board');
            teamBtn.classList.toggle('active', tab === 'team');
            boardPane.classList.toggle('hidden', tab !== 'board');
            teamPane.classList.toggle('hidden', tab !== 'team');
        }

        // Ledger sort (Teacher layout only)
        const sortBtn = document.getElementById('sortLedgerBtn');
        if (sortBtn) {
            let descending = true;
            sortBtn.addEventListener('click', () => {
                const body = document.getElementById('ledger-body');
                const rows = Array.from(body.querySelectorAll('.ledger-item'));
                rows.sort((a, b) => {
                    const pa = parseInt(a.dataset.percent, 10), pb = parseInt(b.dataset.percent, 10);
                    return descending ? pa - pb : pb - pa;
                });
                rows.forEach(r => body.appendChild(r));
                descending = !descending;
                sortBtn.innerHTML = 'Sort by completion <i class="fas fa-arrow-' + (descending ? 'down-short-wide' : 'up-wide-short') + ' ms-1 text-xs"></i>';
            });
        }

        // Contribution chart
        const contribCanvas = document.getElementById('contributionChart');
        if (contribCanvas) {
            const rootStyles = getComputedStyle(document.documentElement);
            const accent = rootStyles.getPropertyValue('--accent').trim() || '#4f46e5';
            const accent2 = rootStyles.getPropertyValue('--accent-2').trim() || '#8b5cf6';
            const border = rootStyles.getPropertyValue('--panel').trim() || '#1f2937';
            const muted = rootStyles.getPropertyValue('--muted').trim() || '#9ca3af';
            const statusRed = rootStyles.getPropertyValue('--status-red').trim() || '#b42318';
            const statusGreen = rootStyles.getPropertyValue('--status-green').trim() || '#15803d';
            new Chart(contribCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: window.PMS_TEAM_ROSTER && window.PMS_TEAM_ROSTER.length > 0 ? window.PMS_TEAM_ROSTER.map(m => m.name.split(' ')[0]) : ['No data'],
                    datasets: [{ 
                        data: window.PMS_TEAM_ROSTER && window.PMS_TEAM_ROSTER.length > 0 ? window.PMS_TEAM_ROSTER.map(m => m.percent) : [100], 
                        backgroundColor: [accent2, accent, muted, statusRed, statusGreen], 
                        borderColor: border, 
                        borderWidth: 2, 
                        hoverOffset: 4 
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: { legend: { position: 'bottom', labels: { color: muted, usePointStyle: true, boxWidth: 6 } } }
                }
            });
        }

        // Simulated live feed (Only for Demo)
        const feedEvents = window.PMS_IS_DEMO ? [
            { time: "Just now", icon: "fa-upload text-accent-2", text: "<span class='font-semibold'>VARSHINI G</span> uploaded <span class='text-accent-2'>database_schema.sql</span>" },
            { time: "2 mins ago", icon: "fa-check-double text-accent", text: "Dr Latha P H approved <span class='font-semibold'>Phase 1 Report</span>" },
            { time: "1 hr ago", icon: "fa-robot text-accent-2", text: "System: deadline for <span class='font-semibold'>Phase 1 report</span> is in 24 hours." },
            { time: "3 hrs ago", icon: "fa-code-commit text-muted-ui", text: "<span class='font-semibold'>SUSHMITA C M</span> moved a task to Review." }
        ] : [
            { time: "Just now", icon: "fa-info-circle text-muted-ui", text: "Activity logging is currently disabled." }
        ];
        const feedContainer = document.getElementById('activity-feed');
        if (feedContainer) {
            feedEvents.forEach((evt, index) => {
                setTimeout(() => {
                    const el = document.createElement('div');
                el.className = "flex gap-3";
                el.innerHTML = `<div class="mt-1"><i class="fas ${evt.icon}"></i></div><div><p>${evt.text}</p><span class="text-xs text-muted-ui">${evt.time}</span></div>`;
                feedContainer.prepend(el);
            }, index * 800);
        });
        }
        if (feedContainer) {
            setTimeout(() => {
                const el = document.createElement('div');
                el.className = "flex gap-3 transition-all duration-500 ease-out translate-y-[-20px] opacity-0";
                el.innerHTML = `<div class="mt-1"><i class="fas fa-comment-dots text-accent"></i></div><div><p>Prof. Nanda Kumar left a voice note on <span class='font-semibold'>Architecture Draft</span></p><span class="text-xs text-muted-ui">Just now</span></div>`;
                feedContainer.prepend(el);
                requestAnimationFrame(() => el.classList.remove('translate-y-[-20px]', 'opacity-0'));
            }, 5000);
        }

        let simGroupsRunning = false;
        function simulateGroups() {
            if(simGroupsRunning) return;
            simGroupsRunning = true;
            
            const emptyState = document.getElementById('empty-groups-state');
            if(emptyState) emptyState.remove();
            
            const ledgerBody = document.getElementById('ledger-body');
            const stats = document.getElementById('ledger-stats');
            
            const mockGroups = window.PMS_MOCK_GROUPS || [];
            let groupCount = 0;
            let finishedCount = 0;
            
            mockGroups.forEach((g, index) => {
                setTimeout(() => {
                    const near = g.percent >= 90;
                    const ink = g.percent < 50 ? 'var(--status-red)' : (near ? 'var(--status-green)' : 'var(--status-amber)');
                    const statusWord = g.percent < 50 ? 'At risk' : (near ? 'Nearly done' : 'On track');
                    const statusColor = ink;
                    
                    const rowHtml = `
                    <div class="ledger-item flex flex-col view-pane" data-percent="${g.percent}">
                        <div class="ledger-row grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-4 items-center cursor-pointer hover:bg-black/5 transition" onclick="document.getElementById('details-${index}').classList.toggle('hidden'); document.getElementById('chevron-${index}').classList.toggle('rotate-180')">
                            <div class="flex items-center gap-3 min-w-0">
                                ${near ? '<span class="seal flex-none"><i class="fas fa-check"></i></span>' : ''}
                                <span class="font-semibold truncate">${escapeHTML(g.name)}</span>
                                <i class="fas fa-chevron-down text-xs text-muted-ui ml-2 transition-transform duration-200" id="chevron-${index}"></i>
                            </div>
                            <span class="w-20 text-right text-sm text-muted-ui">${g.members}</span>
                            <div class="w-40">
                                <div class="ink-bar-track w-full mb-1">
                                    <div class="ink-bar-fill transition-all duration-1000 ease-out" style="width: 0%; background: ${ink};" id="bar-${index}"></div>
                                </div>
                                <span class="text-xs font-mono-ui text-muted-ui" id="pct-${index}">0%</span>
                            </div>
                            <span class="w-16 text-right text-sm font-semibold" style="color: ${statusColor};">${statusWord}</span>
                        </div>
                        <div id="details-${index}" class="hidden px-14 py-4 bg-black/5 border-b border-ui text-sm">
                            <h4 class="font-semibold mb-1">Project Description</h4>
                            <p class="text-muted-ui mb-3">${escapeHTML(g.desc)}</p>
                            <div class="flex justify-between items-end">
                                <div>
                                    <h4 class="font-semibold mb-1">Team Members</h4>
                                    <ul class="list-disc list-inside text-muted-ui">
                                        ${g.member_names.map(m => `<li>${escapeHTML(m)}</li>`).join('')}
                                    </ul>
                                </div>
                                <a href="dashboard.php?project_id=${g.id}&classroom_id=${window.PMS_CLASSROOM_ID}${window.PMS_IS_DEMO ? '&demo_view=TeacherDrilldown' : ''}" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
                                    View Full Report <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    `;
                    ledgerBody.insertAdjacentHTML('beforeend', rowHtml);
                    
                    // Animate the bar and counter
                    setTimeout(() => {
                        const bar = document.getElementById(`bar-${index}`);
                        const pct = document.getElementById(`pct-${index}`);
                        if(bar) bar.style.width = g.percent + '%';
                        
                        let current = 0;
                        const inc = g.percent / 20;
                        const timer = setInterval(() => {
                            current += inc;
                            if (current >= g.percent) {
                                current = g.percent;
                                clearInterval(timer);
                            }
                            if(pct) pct.innerText = Math.round(current) + '%';
                        }, 50);
                    }, 100);

                    // Update stats
                    groupCount++;
                    if(near) finishedCount++;
                    stats.innerHTML = `${groupCount} groups &middot; ${finishedCount} nearly finished`;
                    
                }, index * 1500); 
            });
        }

window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });