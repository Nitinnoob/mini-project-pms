<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Sign In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="syncspace.css">
    <style>
        html, body { height: 100%; margin: 0; }
        body { display: flex; }

        .auth-shell { display: grid; grid-template-columns: 1fr; min-height: 100vh; width: 100%; }
        @media (min-width: 960px) { .auth-shell { grid-template-columns: 1.05fr 1fr; } }

        /* Left: branding panel */
        .auth-brand {
            background: var(--bg-raised);
            border-right: 1px solid var(--border);
            padding: 3rem;
            display: none;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 960px) { .auth-brand { display: flex; } }

        .auth-brand-mark { display: flex; align-items: center; gap: .9rem; position: relative; z-index: 2; }
        .auth-brand-mark .mark { background: var(--accent); border-radius: var(--radius); padding: .55rem .65rem; }
        .auth-brand-mark .mark i { color: var(--bg); font-size: 1.1rem; }
        .auth-brand-mark .word { font-family: var(--font-head); font-weight: 700; font-size: 1.25rem; letter-spacing: -0.01em; }

        .auth-brand-copy { position: relative; z-index: 2; max-width: 30ch; }
        .auth-brand-copy h2 { font-family: var(--font-head); font-size: 1.9rem; line-height: 1.25; font-weight: 600; margin: 0 0 .85rem; }
        .auth-brand-copy p { color: var(--muted); font-size: .95rem; line-height: 1.6; margin: 0; }

        .auth-brand-foot { position: relative; z-index: 2; font-family: var(--font-mono); font-size: .72rem; color: var(--muted); }

        /* Ghost card motif — quiet nod to the kanban board without being literal */
        .auth-brand .ghost-card {
            position: absolute;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            opacity: .55;
        }
        .auth-brand .ghost-card::before {
            content: ''; position: absolute; top: 14px; left: 14px; right: 14px; height: 8px;
            border-radius: 3px; background: var(--border);
        }
        .gc1 { width: 168px; height: 108px; top: 8%;  right: -30px; transform: rotate(8deg); }
        .gc2 { width: 168px; height: 108px; top: 26%; right: 70px;  transform: rotate(-6deg); opacity: .32; }
        .gc3 { width: 168px; height: 108px; bottom: 14%; right: 10px; transform: rotate(-3deg); opacity: .2; }

        /* Right: form column */
        .auth-form-col { display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.5rem; }
        .auth-form-wrap { width: 100%; max-width: 380px; }
        .auth-mobile-mark { display: flex; align-items: center; gap: .7rem; margin-bottom: 2.25rem; }
        @media (min-width: 960px) { .auth-mobile-mark { display: none; } }
        .auth-mobile-mark .mark { background: var(--accent); border-radius: var(--radius); padding: .45rem .55rem; }
        .auth-mobile-mark .mark i { color: var(--bg); }
        .auth-mobile-mark .word { font-family: var(--font-head); font-weight: 700; font-size: 1.05rem; }

        .auth-form-wrap h1 { font-family: var(--font-head); font-size: 1.5rem; font-weight: 600; margin: 0 0 .35rem; }
        .auth-form-wrap .sub { color: var(--muted); font-size: .875rem; margin: 0 0 1.75rem; }

        .auth-label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .4rem; color: var(--text); }
        .auth-field { position: relative; }
        .auth-field .form-input { padding-right: 2.6rem; }
        .auth-field .toggle-password {
            position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted); cursor: pointer; padding: .35rem;
        }
        .auth-field .toggle-password:hover { color: var(--text); }

        .auth-submit {
            width: 100%; padding: .8rem 1rem; font-weight: 600; font-size: .9rem;
            background: var(--accent-2); color: var(--bg); border: none; border-radius: var(--radius);
            cursor: pointer; transition: opacity .15s; margin-top: .5rem;
        }
        .auth-submit:hover { opacity: .92; }

        .auth-switch { margin-top: 1.75rem; text-align: center; font-size: .85rem; color: var(--muted); }
        .auth-switch a { color: var(--accent-2); text-decoration: none; font-weight: 600; }
        .auth-switch a:hover { text-decoration: underline; }

        .auth-alert {
            font-size: .85rem; padding: .7rem .9rem; border-radius: var(--radius); margin-bottom: 1.25rem;
            border: 1px solid; display: flex; gap: .55rem; align-items: flex-start;
        }
        .auth-alert.danger { background: rgba(240,68,56,.08); border-color: var(--danger); color: var(--danger); }
        .auth-alert.ok { background: rgba(69,208,195,.08); border-color: var(--accent-2); color: var(--accent-2); }

        /* Loading veil */
        #page-loader {
            position: fixed; inset: 0; background: var(--bg); z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            transition: opacity .4s ease, visibility .4s ease;
        }
        .spinner {
            width: 34px; height: 34px; border-radius: 50%;
            border: 3px solid var(--border); border-top-color: var(--accent-2);
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="page-loader"><div class="spinner"></div></div>
<script>
    window.addEventListener('load', function () {
        const loader = document.getElementById('page-loader');
        loader.style.opacity = '0';
        setTimeout(() => { loader.style.visibility = 'hidden'; }, 400);
    });
</script>

<div class="auth-shell">
    <div class="auth-brand">
        <div class="ghost-card gc1"></div>
        <div class="ghost-card gc2"></div>
        <div class="ghost-card gc3"></div>

        <div class="auth-brand-mark">
            <div class="mark"><i class="fas fa-layer-group"></i></div>
            <span class="word">PMS</span>
        </div>

        <div class="auth-brand-copy">
            <h2>One workspace for every mini-project team.</h2>
            <p>Classrooms, project teams, and guide reviews, tracked in one place from kickoff to final submission.</p>
        </div>

        <div class="auth-brand-foot">Built for VTU 5th Sem mini-projects</div>
    </div>

    <div class="auth-form-col">
        <div class="auth-form-wrap">
            <div class="auth-mobile-mark">
                <div class="mark"><i class="fas fa-layer-group"></i></div>
                <span class="word">PMS</span>
            </div>