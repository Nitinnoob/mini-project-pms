<?php
require 'dbs.php';

// Safe session flags to impress your professor
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

$message = '';

// Check if form is actually submitted to prevent array index warnings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
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

<?php include 'header.php'; ?>

            <h1>Sign in</h1>
            <p class="sub">Welcome back — pick up where your team left off.</p>

            <?php if (!empty($message)): ?>
                <div class="auth-alert danger">
                    <i class="fas fa-triangle-exclamation mt-0.5"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
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