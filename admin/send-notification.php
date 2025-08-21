<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $coordinator_id = $_POST['coordinator_id'] ?? null;
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        if ($coordinator_id === 'all') {
            // Send to all coordinators
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'coordinator' AND is_active = 1");
            $stmt->execute();
            $coordinators = $stmt->fetchAll();
            
            foreach ($coordinators as $coordinator) {
                $stmt = $pdo->prepare("INSERT INTO notifications (coordinator_id, title, message) VALUES (?, ?, ?)");
                $stmt->execute([$coordinator['id'], $title, $message]);
            }
            
            $_SESSION['success'] = "Notification sent to all coordinators";
        } else {
            // Send to specific coordinator
            $stmt = $pdo->prepare("INSERT INTO notifications (coordinator_id, title, message) VALUES (?, ?, ?)");
            $stmt->execute([$coordinator_id, $title, $message]);
            
            $_SESSION['success'] = "Notification sent to coordinator";
        }
        
        header("Location: send-notification.php");
        exit();
    }
}

// Get all coordinators
$stmt = $pdo->prepare("SELECT id, username, department FROM users WHERE role = 'coordinator' AND is_active = 1 ORDER BY username");
$stmt->execute();
$coordinators = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="send-notification">
    <h2>Send Notification to Coordinators</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="notification-form">
        <div class="form-group">
            <label for="coordinator_id">Send to:</label>
            <select id="coordinator_id" name="coordinator_id">
                <option value="all">All Coordinators</option>
                <?php foreach ($coordinators as $coordinator): ?>
                    <option value="<?php echo $coordinator['id']; ?>">
                        <?php echo htmlspecialchars($coordinator['username'] . ' (' . $coordinator['department'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required placeholder="Notification title">
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="5" required placeholder="Enter your notification message..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Send Notification
        </button>
    </form>
</div>

<style>
.send-notification {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.notification-form {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #192f5d;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #28a745;
}
</style>

<?php require_once '../includes/footer.php'; ?>