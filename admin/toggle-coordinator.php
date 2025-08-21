<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: manage-coordinators.php");
    exit();
}

$coordinatorId = $_GET['id'];
$action = $_GET['action'];

// Validate action
if (!in_array($action, ['activate', 'deactivate'])) {
    header("Location: manage-coordinators.php");
    exit();
}

// Check if coordinator exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'coordinator'");
$stmt->execute([$coordinatorId]);
$coordinator = $stmt->fetch();

if (!$coordinator) {
    header("Location: manage-coordinators.php");
    exit();
}

// Toggle status
$newStatus = $action === 'activate' ? 1 : 0;
$stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->execute([$newStatus, $coordinatorId]);

header("Location: manage-coordinators.php");
exit();
?>