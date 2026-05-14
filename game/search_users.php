<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';
$stmt = mysqli_prepare($conn, 'SELECT username FROM users WHERE username LIKE ? ORDER BY username ASC LIMIT 8');
mysqli_stmt_bind_param($stmt, 's', $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row['username'];
}

echo json_encode($users);
