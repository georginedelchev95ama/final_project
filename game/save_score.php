<?php
require_once __DIR__ . '/../core/functions.php';
header('Content-Type: application/json');
require_login();
ensure_default_achievements($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$levelId = max(1, (int) ($_POST['level_id'] ?? 1));
$timeMs = max(0, (int) ($_POST['time_ms'] ?? 0));
$moves = max(0, (int) ($_POST['moves'] ?? 0));
$mode = trim($_POST['mode'] ?? 'practice');
$won = (int) ($_POST['won'] ?? 0) === 1;
$keysCollected = max(0, (int) ($_POST['keys_collected'] ?? 0));

if ($mode === 'practice') {
    echo json_encode([
        'ok' => true,
        'message' => $won ? 'Practice level cleared.' : 'Practice run ended.',
        'points_earned' => 0,
        'user' => current_user($conn),
    ]);
    exit();
}

$pointsEarned = 0;
if ($won && $mode === 'challenge') {
    $pointsEarned = ($levelId * 50) + 50;
    if ($timeMs > 0 && $timeMs < 45000) {
        $pointsEarned += 25;
    }
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO scores (user_id, level_id, time_ms, moves, mode, won, keys_collected, points_earned)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        throw new Exception('Could not prepare score insert.');
    }

    $wonInt = $won ? 1 : 0;
    mysqli_stmt_bind_param($stmt, 'iiiisiii', $userId, $levelId, $timeMs, $moves, $mode, $wonInt, $keysCollected, $pointsEarned);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Could not save score.');
    }

    $userUpdate = mysqli_prepare(
        $conn,
        'UPDATE users
         SET points = points + ?,
             games_played = games_played + 1,
             wins = wins + ?,
             best_level = CASE WHEN ? = 1 THEN GREATEST(best_level, ?) ELSE best_level END
         WHERE id = ?'
    );

    if (!$userUpdate) {
        throw new Exception('Could not prepare user update.');
    }

    $wonInt = $won ? 1 : 0;
    mysqli_stmt_bind_param($userUpdate, 'iiiii', $pointsEarned, $wonInt, $wonInt, $levelId, $userId);

    if (!mysqli_stmt_execute($userUpdate)) {
        throw new Exception('Could not update user stats.');
    }

    update_user_title($conn, $userId);
    evaluate_achievements($conn, $userId, $levelId, $won, $keysCollected, $timeMs);

    mysqli_commit($conn);

    $current = current_user($conn);

    echo json_encode([
        'ok' => true,
        'message' => $won ? 'Run saved.' : 'Loss saved.',
        'points_earned' => $pointsEarned,
        'user' => $current,
    ]);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage(),
    ]);
}
?>