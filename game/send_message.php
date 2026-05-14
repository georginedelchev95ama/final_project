<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$currentUser = current_user($conn);
$to   = trim($_POST['to'] ?? '');
$body = trim($_POST['body'] ?? '');

if ($to === '' || $body === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

if (mb_strlen($body) > 500) {
    echo json_encode(['ok' => false, 'error' => 'Message too long']);
    exit;
}

if ($to === $currentUser['username']) {
    echo json_encode(['ok' => false, 'error' => 'Cannot message yourself']);
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $to);
mysqli_stmt_execute($stmt);
$receiver = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$receiver) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

$insert = mysqli_prepare($conn, 'INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($insert, 'iis', $currentUser['id'], $receiver['id'], $body);
mysqli_stmt_execute($insert);

echo json_encode(['ok' => true, 'id' => mysqli_insert_id($conn)]);
