<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('COM6011_APP_SESSION');
    session_start();
}

// Clever Cloud injects MYSQL_ADDON_* automatically when the add-on is linked.
// Fallback values are for local development only.
$host     = getenv('MYSQL_ADDON_HOST')     ?: 'localhost';
$dbname   = getenv('MYSQL_ADDON_DB')       ?: 'maze_escape_local';
$username = getenv('MYSQL_ADDON_USER')     ?: 'root';
$password = getenv('MYSQL_ADDON_PASSWORD') ?: '';
$port     = (int)(getenv('MYSQL_ADDON_PORT') ?: 3306);

$conn = mysqli_connect($host, $username, $password, $dbname, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

// Reconnect if the connection dropped (common on Clever Cloud free tier)
if (!mysqli_ping($conn)) {
    mysqli_close($conn);
    $conn = mysqli_connect($host, $username, $password, $dbname, $port);
    if (!$conn) {
        die("Database reconnection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');
}
?>
