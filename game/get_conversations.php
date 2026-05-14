<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([]);
    exit;
}

$currentUser = current_user($conn);
$uid = (int) $currentUser['id'];

$stmt = mysqli_prepare($conn, '
    SELECT
        partner.username,
        partner.last_seen,
        latest.body       AS last_message,
        latest.created_at AS last_at,
        COALESCE(unread.cnt, 0) AS unread_count
    FROM (
        SELECT
            CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
            MAX(id) AS max_id
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY partner_id
    ) conv
    JOIN messages latest ON latest.id = conv.max_id
    JOIN users partner   ON partner.id = conv.partner_id
    LEFT JOIN (
        SELECT sender_id, COUNT(*) AS cnt
        FROM messages
        WHERE receiver_id = ? AND read_at IS NULL
        GROUP BY sender_id
    ) unread ON unread.sender_id = conv.partner_id
    ORDER BY latest.created_at DESC
    LIMIT 20
');
mysqli_stmt_bind_param($stmt, 'iiii', $uid, $uid, $uid, $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$conversations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $conversations[] = [
        'username'     => $row['username'],
        'last_message' => mb_substr($row['last_message'], 0, 60) . (mb_strlen($row['last_message']) > 60 ? '…' : ''),
        'last_at'      => $row['last_at'],
        'unread_count' => (int) $row['unread_count'],
        'online'       => is_user_online($row['last_seen'] ?? null),
    ];
}

echo json_encode($conversations);
