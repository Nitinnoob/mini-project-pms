<?php
require 'dbs.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'])) {
     $username = trim($_POST['username']);
     $password = $_POST['password'];
     $role = isset($_POST['role']) ? $_POST['role'] : 'Developer';

     if(!empty($username) && !empty($password)) {
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);
     
          try {
               // Inserts into the Oracle table structure we configured earlier
               $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
               $stmt->execute([$username, $hashed_password, $role]);
               $message = "Account created successfully! You can now log in.";
          } catch(PDOException $e) {
               $message = "Error: Username might already be taken.";
          }
      } else {
          $message = "Please fill in all fields.";
      }
}
?>

<?php include 'header.php'; ?>

<div class="container auth-container">
    <div class="card shadow-lg p-4 custom-card border-0">
        <div class="card-body">
            <h3 class="card-title text-center mb-4 fw-bold text-dark">Create Account</h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo (strpos($message, 'successfully') !== false) ? 'alert-success' : 'alert-danger'; ?> py-2 text-center small">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Your Role</label>
                    <select name="role" class="form-select">
                        <option value="Developer">Developer</option>
                        <option value="Project Manager">Project Manager</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-custom w-100 fw-bold py-2 mt-2">Sign Up</button>
            </form>
            
            <div class="text-center mt-3 small">
                <span class="text-muted">Already registered?</span> 
                <a href="login.php" class="text-decoration-none">Log In</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
