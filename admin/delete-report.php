<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete the report
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to reports list
    header("Location: reports.php?msg=Report+deleted+successfully");
    exit;
} else {
    header("Location: reports.php?error=Invalid+report+ID");
    exit;
}
