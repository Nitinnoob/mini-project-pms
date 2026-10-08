const escapeHTML = (str) => {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
};

const TEACHER_TABS = ['groups', 'roster', 'phases'];

function switchTeacherTab(tab) {
    if (!TEACHER_TABS.includes(tab)) tab = 'groups';
    TEACHER_TABS.forEach(function (name) {
        const btn = document.querySelector(`[data-tab="teacher-${name}"]`) || document.getElementById('btn-tab-' + name);
        const pane = document.querySelector(`[data-tab-pane="teacher-${name}"]`) || document.getElementById('tab-' + name);
        if (btn) btn.classList.toggle('active', name === tab);
        if (pane) pane.classList.toggle('hidden', name !== tab);
    });
}

// Global modal handling using data attributes (data-modal-target and data-modal-close)
document.addEventListener('click', function(e) {
    // Open modal trigger
    const openTrigger = e.target.closest('[data-modal-target]');
    if (openTrigger) {
        const targetSelector = openTrigger.getAttribute('data-modal-target');
        const modal = targetSelector.startsWith('#') || targetSelector.startsWith('.')
            ? document.querySelector(targetSelector)
            : document.getElementById(targetSelector);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('show');
        }
        return;
    }

    // Close modal trigger
    const closeTrigger = e.target.closest('[data-modal-close]');
    if (closeTrigger) {
        const closeSelector = closeTrigger.getAttribute('data-modal-close');
        let modal = null;
        if (closeSelector) {
            modal = closeSelector.startsWith('#') || closeSelector.startsWith('.')
                ? document.querySelector(closeSelector)
                : document.getElementById(closeSelector);
        } else {
            modal = closeTrigger.closest('.modal-backdrop, .modal, [id$="Modal"]');
        }
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('show');
        }
    }
});

// Phase edits bounce back here with ?tab=phases so the coordinator lands on the tab they edited.
(function () {
    const requested = new URLSearchParams(window.location.search).get('tab');
    if (requested && TEACHER_TABS.includes(requested)) {
        switchTeacherTab(requested);
    }
})();



// Kanban drag and drop (Student layout only)
        function showToast(msg, title) {
          const t = document.getElementById('toast');
          if (!t) return;
          const h4 = t.querySelector('h4');
          const p  = t.querySelector('p');
          if (h4) h4.textContent = title || 'Task completed';
          if (p)  p.textContent  = msg;
          t.classList.add('show');
          setTimeout(() => t.classList.remove('show'), 3000);
        }

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
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) fd.append('csrf_token', csrfToken);
                
                fetch('update_task_status.php', {
                    method: 'POST',
                    headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
                    body: fd
                })
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

        // Leader tabs: My Board <-> Team <-> Logs
        function switchLeaderTab(tab) {
            const tabs = ['board', 'team', 'logs'];
            tabs.forEach(name => {
                const btn = document.querySelector(`[data-tab="leader-${name}"]`) || document.getElementById('btn-tab-' + name);
                const pane = document.querySelector(`[data-tab-pane="leader-${name}"]`) || document.getElementById('leader-tab-' + name);
                if (btn) btn.classList.toggle('active', name === tab);
                if (pane) pane.classList.toggle('hidden', name !== tab);
            });
        }

        // Ledger sort (Teacher layout only)
        const sortBtn = document.querySelector('[data-action="sort-ledger"]') || document.getElementById('sortLedgerBtn');
        if (sortBtn) {
            let descending = true;
            sortBtn.addEventListener('click', () => {
                const body = document.querySelector('[data-ledger-body]') || document.getElementById('ledger-body');
                if (!body) return;
                const rows = Array.from(body.querySelectorAll('.ledger-item, [data-percent]'));
                rows.sort((a, b) => {
                    const pa = parseInt(a.dataset.percent, 10) || 0, pb = parseInt(b.dataset.percent, 10) || 0;
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
            let contribChart = null;

            function updateContribChart() {
                const rootStyles = getComputedStyle(document.documentElement);
                const accent = rootStyles.getPropertyValue('--accent').trim() || '#4f46e5';
                const accent2 = rootStyles.getPropertyValue('--accent-2').trim() || '#8b5cf6';
                const border = rootStyles.getPropertyValue('--panel').trim() || '#1f2937';
                const muted = rootStyles.getPropertyValue('--muted').trim() || '#9ca3af';
                const statusRed = rootStyles.getPropertyValue('--status-red').trim() || '#b42318';
                const statusGreen = rootStyles.getPropertyValue('--status-green').trim() || '#15803d';

                if (contribChart) {
                    contribChart.data.datasets[0].backgroundColor = [accent2, accent, muted, statusRed, statusGreen];
                    contribChart.data.datasets[0].borderColor = border;
                    contribChart.options.plugins.legend.labels.color = muted;
                    contribChart.update();
                    return;
                }

                contribChart = new Chart(contribCanvas.getContext('2d'), {
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

            updateContribChart();
            window.addEventListener('themeToggled', updateContribChart);
        }



window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });