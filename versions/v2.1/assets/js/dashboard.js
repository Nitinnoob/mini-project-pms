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
            const logsBtn = document.getElementById('btn-tab-logs');
            const boardPane = document.getElementById('leader-tab-board');
            const teamPane = document.getElementById('leader-tab-team');
            const logsPane = document.getElementById('leader-tab-logs');

            if (boardBtn) boardBtn.classList.toggle('active', tab === 'board');
            if (teamBtn) teamBtn.classList.toggle('active', tab === 'team');
            if (logsBtn) logsBtn.classList.toggle('active', tab === 'logs');

            if (boardPane) boardPane.classList.toggle('hidden', tab !== 'board');
            if (teamPane) teamPane.classList.toggle('hidden', tab !== 'team');
            if (logsPane) logsPane.classList.toggle('hidden', tab !== 'logs');
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



window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });