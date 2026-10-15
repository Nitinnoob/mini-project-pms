// Teacher tabs
const TEACHER_TABS = ['groups', 'roster', 'marks'];

function switchTeacherTab(tab) {
    if (!TEACHER_TABS.includes(tab)) tab = 'groups';
    TEACHER_TABS.forEach(function (name) {
        const btn = document.getElementById('btn-tab-' + name);
        const pane = document.getElementById('tab-' + name);
        if (btn) btn.classList.toggle('active', name === tab);
        if (pane) pane.classList.toggle('hidden', name !== tab);
    });
}

// Project tabs (student view)
const PROJECT_TABS = ['team', 'meetings'];

function switchProjectTab(tab) {
    if (!PROJECT_TABS.includes(tab)) tab = 'team';
    PROJECT_TABS.forEach(function (name) {
        const btn = document.getElementById('btn-tab-' + name);
        const pane = document.getElementById('project-tab-' + name);
        if (btn) btn.classList.toggle('active', name === tab);
        if (pane) pane.classList.toggle('hidden', name !== tab);
    });
}

// Global modal handling using data attributes
document.addEventListener('click', function(e) {
    const openTrigger = e.target.closest('[data-modal-target]');
    if (openTrigger) {
        const targetSelector = openTrigger.getAttribute('data-modal-target');
        const modal = document.getElementById(targetSelector) || document.querySelector(targetSelector);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('show');
        }
        return;
    }

    const closeTrigger = e.target.closest('[data-modal-close]');
    if (closeTrigger) {
        const closeSelector = closeTrigger.getAttribute('data-modal-close');
        let modal = null;
        if (closeSelector) {
            modal = document.getElementById(closeSelector) || document.querySelector(closeSelector);
        } else {
            modal = closeTrigger.closest('.modal-backdrop, .modal, [id$="Modal"]');
        }
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('show');
        }
        return;
    }
});

// URL fragment handler for tab routing (e.g. #tab-roster from redirects)
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        if (TEACHER_TABS.includes(hash.replace('tab-', ''))) {
            switchTeacherTab(hash.replace('tab-', ''));
        } else if (PROJECT_TABS.includes(hash.replace('project-tab-', ''))) {
            switchProjectTab(hash.replace('project-tab-', ''));
        }
    }
});