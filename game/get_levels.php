<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($conn, 'SELECT id, name, difficulty, level_json FROM levels WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $level = mysqli_fetch_assoc($result);

    if (!$level) {
        http_response_code(404);
        echo json_encode(['error' => 'Level not found']);
        exit();
    }

    echo json_encode([
        'id' => (int) $level['id'],
        'name' => $level['name'],
        'difficulty' => (int) $level['difficulty'],
        'data' => json_decode($level['level_json'], true),
    ]);
    exit();
}

$result = mysqli_query($conn, 'SELECT id, name, difficulty FROM levels ORDER BY difficulty ASC, id ASC');
$levels = [];

while ($row = mysqli_fetch_assoc($result)) {
    $levels[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'difficulty' => (int) $row['difficulty'],
    ];
}

echo json_encode(['levels' => $levels]);
?>