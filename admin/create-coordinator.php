<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $tempPassword = $_POST['temp_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    if (empty($tempPassword)) {
        $errors[] = "Temporary password is required";
    }
    
    if (empty($errors)) {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists";
        } else {
            // Hash the temporary password
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            // Create the coordinator
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, department) VALUES (?, ?, ?, 'coordinator', ?)");
            $stmt->execute([$username, $email, $hashedPassword, $department]);
            
            // TODO: Send email with setup instructions
            
            header("Location: manage-coordinators.php");
            exit();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="create-coordinator">
    <h2>Create New Coordinator</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" required>
        </div>
        
        <div class="form-group">
            <label for="temp_password">Temporary Password</label>
            <input type="password" id="temp_password" name="temp_password" required>
        </div>
        
        <button type="submit" class="btn">Create Coordinator</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>