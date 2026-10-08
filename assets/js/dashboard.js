const escapeHTML = (str) => {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
};

const TEACHER_TABS = ['groups', 'roster', 'marks', 'milestones', 'phases'];

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

// Leader tabs: Team <-> Logs
function switchLeaderTab(tab) {
    const tabs = ['team', 'logs'];
    tabs.forEach(name => {
        const btn = document.querySelector(`[data-tab="leader-${name}"]`) || document.getElementById('btn-tab-' + name);
        const pane = document.querySelector(`[data-tab-pane="leader-${name}"]`) || document.getElementById('leader-tab-' + name);
        if (btn) btn.classList.toggle('active', name === tab);
        if (pane) pane.classList.toggle('hidden', name !== tab);
    });
}

// Toast utility (kept for general purpose notifications)
function showToast(msg, title) {
    const t = document.getElementById('toast');
    if (!t) return;
    const h4 = t.querySelector('h4');
    const p  = t.querySelector('p');
    if (h4) h4.textContent = title || 'Success';
    if (p)  p.textContent  = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
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

window.addEventListener('pageshow', function (event) {
    if (event.persisted) { window.location.reload(); }
});