<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: coordinator/dashboard.php");
    }
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>