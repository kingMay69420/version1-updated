<?php
require_once 'config.php';  // Add this line first
require_once 'db.php';


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCoordinator() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'coordinator';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../coordinator/dashboard.php");
        exit();
    }
}

function redirectIfNotCoordinator() {
    redirectIfNotLoggedIn();
    if (!isCoordinator()) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
?>