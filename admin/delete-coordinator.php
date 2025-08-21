<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-coordinators.php");
    exit();
}

$coordinatorId = $_GET['id'];

// Check if coordinator has any reports
$stmt = $pdo->prepare("SELECT id FROM reports WHERE coordinator_id = ?");
$stmt->execute([$coordinatorId]);

if ($stmt->rowCount() > 0) {
    $_SESSION['error'] = "Cannot delete coordinator with existing reports";
    header("Location: manage-coordinators.php");
    exit();
}

// Delete coordinator
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'coordinator'");
$stmt->execute([$coordinatorId]);

header("Location: manage-coordinators.php");
exit();
?>