<!DOCTYPE html>
<html lang="en" class="dark" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <title>PMS — Sign In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="pms.css">
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