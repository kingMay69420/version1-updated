<?php
require_once 'config.php';

$host = 'localhost';
$dbname = 'reporting_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br>Please check your MySQL server status and database credentials in includes/db.php."); // Keep the error message for debugging
}


?>