<?php
// Session (httponly, SameSite=Lax, strict mode) + $pdo come from the shared bootstrap.
require_once 'bootstrap.php';

$message = '';

// Check if form is actually submitted to prevent array index warnings
if (is_post() && isset($_POST['username'], $_POST['password'])) {
    csrf_verify();
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // New session id on privilege change — prevents session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        // Global 'role' is ignored. Contextual roles are set per classroom in hub.php.

        header("Location: hub.php");
        exit;
    } else {
        $message = "Invalid username or password.";
    }
}
?>

<?php include 'auth_header.php'; ?>

            <h1>Sign in</h1>
            <p class="sub">Welcome back — pick up where your team left off.</p>

            <?php if (!empty($message)): ?>
                <div class="auth-alert danger">
                    <i class="fas fa-triangle-exclamation mt-0.5"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-5">
                    <label class="auth-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Enter your username" required autofocus>
                </div>

                <div class="mb-5">
                    <label class="auth-label">Password</label>
                    <div class="auth-field">
                        <input type="password" name="password" id="loginPassword" class="form-input" placeholder="Enter your password" required>
                        <button class="toggle-password" type="button" data-target="loginPassword" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">Log in</button>
            </form>

            <div class="auth-switch">
                Don't have an account? <a href="registers.php">Sign up</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = document.getElementById(this.getAttribute('data-target'));
        const icon = this.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });
});
</script>

</body>
</html>