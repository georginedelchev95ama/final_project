<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('COM6011_APP_SESSION');
    session_start();
}

$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'h432x3i_com6011_maze_test3';
$username = getenv('DB_USER') ?: 'h432x3i_user1';
$password = getenv('DB_PASS') ?: '1A93y}_^kyIP@&*y';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>
