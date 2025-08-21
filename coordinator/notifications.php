<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE coordinator_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: notifications.php");
    exit();
}

// Get notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE coordinator_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Mark as read when viewing
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE coordinator_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);

require_once '../includes/header.php';
?>

<div class="notifications-page">
    <div class="page-header">
        <h2>Notifications</h2>
        <?php if (!empty($notifications)): ?>
            <a href="?mark_all_read=true" class="btn btn-secondary">
                <i class="fas fa-check-double"></i> Mark All as Read
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications yet</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-header">
                        <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                        <span class="notification-time">
                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                        </span>
                    </div>
                    <div class="notification-body">
                        <p><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.notifications-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1.5rem; /* Add this line */
}

/* Add these new styles for the button */


/* Responsive design for smaller screens */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #192f5d;
}

.notification-card.unread {
    border-left-color: #007bff;
    background: #f8f9fa;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.notification-header h4 {
    margin: 0;
    color: #192f5d;
    font-size: 1.1rem;
}

.notification-time {
    color: #666;
    font-size: 0.85rem;
}

.notification-body p {
    margin: 0;
    color: #333;
    line-height: 1.5;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ccc;
}
</style>

<?php require_once '../includes/footer.php'; ?>