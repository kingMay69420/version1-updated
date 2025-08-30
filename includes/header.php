<?php
require_once __DIR__ . '/auth.php'; // keep

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

    <!-- Global styles -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">

    <!-- Page-scoped / optional styles -->
    <?php
    // If a page sets $EXTRA_CSS = ['/assets/css/login.css', ...] we include them.
    if (!empty($EXTRA_CSS) && is_array($EXTRA_CSS)) {
        foreach ($EXTRA_CSS as $href) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">' . PHP_EOL;
        }
    }

    // Or, include login.css when the page sets either of these flags:
    if (!empty($LOAD_LOGIN_CSS) || (isset($BODY_CLASS) && $BODY_CLASS === 'login')): ?>
        <link rel="stylesheet" href="/assets/css/login.css">
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="<?= isset($BODY_CLASS) ? htmlspecialchars($BODY_CLASS) : '' ?>">
<?php if (empty($HIDE_CHROME)): ?>
    <header>
        <div class="container">
            <h1>SMCC COMMUNITY EXTENSION SERVICE</h1>
            <nav>
                <?php if (isLoggedIn() && isCoordinator()): ?>
                    <?php
                    // Use correct path base
                    require_once __DIR__ . '/db.php';
                    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE coordinator_id = ? AND is_read = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $unread = $stmt->fetch()['unread_count'];
                    ?>
                    <a href="/coordinator/notifications.php" class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread > 0): ?>
                            <span class="notification-badge"><?= (int)$unread ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
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
                    <a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <a href="/admin/send-notification.php"><i class="fas fa-bell"></i> Send Notifications</a>
                    <a href="/admin/chat.php"><i class="fas fa-comments"></i> Chat</a>
                <?php elseif (isCoordinator()): ?>
                    <a href="/coordinator/dashboard.php">
                    <i class="fa-solid fa-house"></i> Home
                    </a>

                    <a href="">
                    <i class="fa-solid fa-clipboard-list"></i> Report Management
                    </a>

                    <a href="/coordinator/history.php">
                    <i class="fa-solid fa-clock-rotate-left"></i> Report History
                    </a>
                    <a href="/coordinator/chat.php"><i class="fas fa-comments"></i> Chat</a>

                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="content">
<?php else: ?>
    <!-- Chrome hidden (e.g., login page) -->
    <main class="content">
<?php endif; ?>
