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

    // Oracle queries pull associative rows natively matching uppercase columns
    if ($user && password_verify($password, $user['PASSWORD'])) { 
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['username'] = $user['USERNAME'];
        // Global 'ROLE' is ignored. Contextual roles will be set in hub.php.
        
        header("Location: hub.php");
        exit;
    } else {
        $message = "Invalid username or password.";
    }
}
?>

<?php include 'header.php'; ?>

<div class="container auth-container">
    <div class="card shadow-lg p-4 custom-card border-0">
        <div class="card-body">
            <h3 class="card-title text-center mb-4 fw-bold text-dark">Sign In</h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-danger py-2 text-center small" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="loginPassword" style="border-color: #dee2e6;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-custom w-100 fw-bold py-2 mt-2">Log In</button>
            </form>
            
            <div class="text-center mt-3 small">
                <span class="text-muted">Don't have an account?</span> 
                <a href="registers.php" class="text-decoration-none">Sign Up</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});
</script>

</body>
</html>
