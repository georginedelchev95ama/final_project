<?php
require_once __DIR__ . '/../core/functions.php';
redirect_if_logged_in('pages/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('pages/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    set_flash('error', 'Please enter both username and password.');
    redirect_to('pages/login.php');
}

$stmt = mysqli_prepare($conn, 'SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password_hash'])) {
    set_flash('error', 'Wrong username or password.');
    redirect_to('pages/login.php');
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];

set_flash('success', 'Welcome back, ' . $user['username'] . '.');
redirect_to('pages/index.php');
?>