<?php
require_once __DIR__ . '/../core/functions.php';
redirect_if_logged_in('pages/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('pages/register.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$repeatPassword = $_POST['repeat_password'] ?? '';

if ($username === '' || $password === '' || $repeatPassword === '') {
    set_flash('error', 'Please fill in all fields.');
    redirect_to('pages/register.php');
}
if (strlen($username) < 3) {
    set_flash('error', 'Username must be at least 3 characters long.');
    redirect_to('pages/register.php');
}
if (strlen($password) < 6) {
    set_flash('error', 'Password must be at least 6 characters long.');
    redirect_to('pages/register.php');
}
if ($password !== $repeatPassword) {
    set_flash('error', 'Passwords do not match.');
    redirect_to('pages/register.php');
}

$check = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($check, 's', $username);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    set_flash('error', 'That username already exists. Try another one.');
    redirect_to('pages/register.php');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password_hash) VALUES (?, ?)');
mysqli_stmt_bind_param($stmt, 'ss', $username, $passwordHash);

if (!mysqli_stmt_execute($stmt)) {
    set_flash('error', 'Could not create account.');
    redirect_to('pages/register.php');
}

ensure_default_achievements($conn);
set_flash('success', 'Account created. Please log in.');
redirect_to('pages/login.php');
?>