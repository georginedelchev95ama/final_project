<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([]);
    exit;
}

$currentUser  = current_user($conn);
$withUsername = trim($_GET['with'] ?? '');
$afterId      = (int) ($_GET['after'] ?? 0);

if ($withUsername === '') {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $withUsername);
mysqli_stmt_execute($stmt);
$other = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$other) {
    echo json_encode([]);
    exit;
}

$markRead = mysqli_prepare($conn, '
    UPDATE messages SET read_at = NOW()
    WHERE receiver_id = ? AND sender_id = ? AND read_at IS NULL
');
mysqli_stmt_bind_param($markRead, 'ii', $currentUser['id'], $other['id']);
mysqli_stmt_execute($markRead);

$stmt = mysqli_prepare($conn, '
    SELECT m.id, u.username AS sender, m.body, m.created_at
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE (
        (m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?)
    )
    AND m.id > ?
    ORDER BY m.created_at ASC
    LIMIT 100
');
mysqli_stmt_bind_param($stmt, 'iiiii',
    $currentUser['id'], $other['id'],
    $other['id'], $currentUser['id'],
    $afterId
);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

echo json_encode($messages);
