<?php
require_once __DIR__ . '/auth.php'; // Add this line at the top

if (!function_exists('isLoggedIn')) {
    die('Authentication functions not loaded');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Accomplishment Report System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>SMCC COMMUNITY EXTENSION SERVICE</h1>
            <nav>
                <!-- Add this to your header.php file inside the nav section -->
<?php if (isLoggedIn() && isCoordinator()): ?>
    <?php
    require_once '../includes/db.php';
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE coordinator_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread = $stmt->fetch()['unread_count'];
    ?>
    <a href="/coordinator/notifications.php" class="notification-bell">
        <i class="fas fa-bell"></i>
        <?php if ($unread > 0): ?>
            <span class="notification-badge"><?php echo $unread; ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo $_SESSION['username']; ?>!</span>
                    <a href="/logout.php">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="main-container">
        <?php if (isLoggedIn()): ?>
        <div class="sidebar">
            <?php if (isAdmin()): ?>
                <a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/admin/manage-coordinators.php"><i class="fas fa-users"></i> Manage Coordinators</a>
               <!-- Add this line to the admin sidebar section -->
                <a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
               
                <a href="/admin/send-notification.php"><i class="fas fa-bell"></i> Send Notifications</a>
            <?php elseif (isCoordinator()): ?>
                <a href="/coordinator/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/coordinator/history.php"><i class="fas fa-history"></i> Report History</a>
              
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="content">