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
        $_SESSION['role'] = $user['ROLE'];
        
        header("Location: dashboard.php");
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
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                
                <button type="submit" class="btn btn-custom w-100 fw-bold py-2 mt-2">Log In</button>
            </form>
            
            <div class="text-center mt-3 small">
                <span class="text-muted">Don't have an account?</span> 
                <a href="register.php" class="text-decoration-none">Sign Up</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
