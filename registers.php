<?php
require 'dbs.php';
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'])) {
     $username = trim($_POST['username']);
     $password = $_POST['password'];
     if (!empty($username) && !empty($password)) {
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          try {
               // 'role' is explicitly NULL — roles are contextual to classrooms, not global.
               $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, NULL)");
               $stmt->execute([$username, $hashed_password]);
               $message = "Account created successfully. You can now log in.";
               $success = true;
          } catch (PDOException $e) {
               $message = "That username might already be taken.";
          }
     } else {
          $message = "Please fill in all fields.";
     }
}
?>

<?php include 'header.php'; ?>

            <h1>Create your account</h1>
            <p class="sub">You'll join or create classrooms once you're signed in.</p>

            <?php if (!empty($message)): ?>
                <div class="auth-alert <?php echo $success ? 'ok' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $success ? 'circle-check' : 'triangle-exclamation'; ?> mt-0.5"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-5">
                    <label class="auth-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Choose a username" required autofocus>
                </div>

                <div class="mb-5">
                    <label class="auth-label">Password</label>
                    <div class="auth-field">
                        <input type="password" name="password" id="registerPassword" class="form-input" placeholder="Create a password" required>
                        <button class="toggle-password" type="button" data-target="registerPassword" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">Sign up</button>
            </form>

            <div class="auth-switch">
                Already registered? <a href="login.php">Log in</a>
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