<?php
require 'dbs.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'])) {
     $username = trim($_POST['username']);
     $password = $_POST['password'];
     if(!empty($username) && !empty($password)) {
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);
     
          try {
               // Inserts into the Oracle table structure we configured earlier. 'role' is explicitly NULL.
               $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, NULL)");
               $stmt->execute([$username, $hashed_password]);
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
                    <div class="input-group">
                        <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Create a password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="registerPassword" style="border-color: #dee2e6;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Role selection removed: roles are now contextual to classrooms -->
                
                <button type="submit" class="btn btn-custom w-100 fw-bold py-2 mt-2">Sign Up</button>
            </form>
            
            <div class="text-center mt-3 small">
                <span class="text-muted">Already registered?</span> 
                <a href="login.php" class="text-decoration-none">Log In</a>
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
