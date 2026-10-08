// Notification bell: polls notifications.php, renders the dropdown, marks items read.
(function () {
    var btn = document.getElementById('notifBellBtn');
    var panel = document.getElementById('notifPanel');
    var list = document.getElementById('notifList');
    var badge = document.getElementById('notifBadge');
    var readAll = document.getElementById('notifReadAll');
    if (!btn || !panel || !list) return;

    var ICONS = {
        join_request: 'fa-user-plus',
        join_accepted: 'fa-circle-check',
        join_declined: 'fa-circle-xmark',
        invite: 'fa-envelope',
        invite_response: 'fa-envelope-open',
        task_assigned: 'fa-list-check',
        task_updated: 'fa-exchange-alt',
        weekly_log: 'fa-file-lines',
        review: 'fa-clipboard-check',
        issue_raised: 'fa-triangle-exclamation',
        issue_resolved: 'fa-circle-check'
    };

    function timeAgo(s) {
        var d = new Date(s.replace(' ', 'T'));
        var sec = Math.max(0, (Date.now() - d.getTime()) / 1000);
        if (isNaN(sec)) return '';
        if (sec < 60) return 'just now';
        if (sec < 3600) return Math.floor(sec / 60) + ' min ago';
        if (sec < 86400) return Math.floor(sec / 3600) + ' h ago';
        return Math.floor(sec / 86400) + ' d ago';
    }

    function post(params) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        var headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
            params = Object.assign({}, params, { csrf_token: csrfToken });
        }
        return fetch('notifications.php', {
            method: 'POST',
            headers: headers,
            body: new URLSearchParams(params)
        });
    }

    function setBadge(n) {
        if (n > 0) {
            badge.textContent = n > 99 ? '99+' : n;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function render(data) {
        setBadge(data.unread);
        list.textContent = '';
        if (!data.items.length) {
            var empty = document.createElement('div');
            empty.className = 'p-6 text-sm text-muted-ui text-center';
            empty.innerHTML = '<i class="fas fa-bell-slash text-2xl mb-2 opacity-50 block"></i>You\'re all caught up.';
            list.appendChild(empty);
            return;
        }
        data.items.forEach(function (n) {
            var row = document.createElement('a');
            row.href = n.link || '#';
            row.className = 'flex gap-3 px-4 py-3 border-b border-ui hover-overlay-subtle transition';
            if (n.is_read == 0) row.style.background = 'rgba(var(--accent-rgb, 31,111,92), 0.08)';

            var ic = document.createElement('i');
            ic.className = 'fas ' + (ICONS[n.type] || 'fa-bell') + ' mt-1 ' + (n.is_read == 0 ? 'text-accent' : 'text-muted-ui');
            var body = document.createElement('div');
            body.className = 'min-w-0';
            var msg = document.createElement('p');
            msg.className = 'text-sm ' + (n.is_read == 0 ? 'font-semibold' : '');
            msg.textContent = n.message;
            var when = document.createElement('span');
            when.className = 'text-xs text-muted-ui';
            when.textContent = timeAgo(n.created_at);
            body.appendChild(msg);
            body.appendChild(when);
            row.appendChild(ic);
            row.appendChild(body);

            row.addEventListener('click', function (e) {
                if (n.is_read == 0) {
                    e.preventDefault();
                    post({ action: 'read', id: n.id }).finally(function () {
                        if (n.link) window.location.href = n.link; else load();
                    });
                } else if (!n.link) {
                    e.preventDefault();
                }
            });
            list.appendChild(row);
        });
    }

    function load() {
        return fetch('notifications.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = panel.classList.toggle('hidden') === false;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) load();
    });
    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
    readAll.addEventListener('click', function () {
        post({ action: 'read_all' }).then(load);
    });

    load();
    setInterval(function () { if (panel.classList.contains('hidden')) load(); }, 60000);
})();
