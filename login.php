<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Case-insensitive username comparison
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND is_active = TRUE");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found or account inactive');
        }

        if (!password_verify($password, $user['password'])) {
            // Log failed attempts (remove in production)
            error_log("Failed login attempt for username: $username");
            throw new Exception('Invalid credentials');
        }

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'department' => $user['department'],
            'last_login' => time()
        ];

        // Redirect based on role
        $redirect = ($user['role'] === 'admin') 
            ? 'admin/dashboard.php' 
            : 'coordinator/dashboard.php';
        
        header("Location: $redirect");
        exit();

    } catch (Exception $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <h2>Login</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>