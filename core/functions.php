<?php
require_once __DIR__ . '/db.php';

function app_root_url(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $directory = str_replace('\\', '/', dirname($scriptName));

    if ($directory === '/' || $directory === '.') {
        $directory = '';
    }

    foreach (['/pages', '/auth', '/game', '/core', '/css', '/js', '/sql', '/admin'] as $suffix) {
        if ($directory === $suffix) {
            $directory = '';
            break;
        }

        if ($directory !== '' && substr($directory, -strlen($suffix)) === $suffix) {
            $directory = substr($directory, 0, -strlen($suffix));
            break;
        }
    }

    return rtrim($directory, '/');
}

function app_url(string $path = ''): string
{
    $root = app_root_url();

    if ($path === '') {
        return $root === '' ? '' : $root;
    }

    return ($root === '' ? '' : $root) . '/' . ltrim($path, '/');
}

function redirect_to(string $path): void
{
    header('Location: ' . app_url($path));
    exit();
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function render_flash(): void
{
    $flash = get_flash();

    if (!$flash) {
        return;
    }

    echo '<div class="alert ' . esc($flash['type']) . '">' . esc($flash['message']) . '</div>';
}

function esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function current_user(mysqli $conn): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    $userId = (int) $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, 'SELECT id, username, points, wins, games_played, best_level, title, is_admin FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}

function require_login(): void
{
    global $conn;

    if (!is_logged_in() || !current_user($conn)) {
        unset($_SESSION['user_id'], $_SESSION['username']);
        set_flash('error', 'Please log in first.');
        redirect_to('pages/login.php');
    }
}

function redirect_if_logged_in(string $location = 'pages/index.php'): void
{
    global $conn;

    if (is_logged_in() && current_user($conn)) {
        set_flash('info', 'You are already logged in.');
        redirect_to($location);
    }

    if (is_logged_in() && !current_user($conn)) {
        unset($_SESSION['user_id'], $_SESSION['username']);
    }
}

function is_admin_user(array $user): bool
{
    return !empty($user['is_admin']);
}

function require_admin(mysqli $conn): array
{
    require_login();
    $user = current_user($conn);

    if (!$user || !is_admin_user($user)) {
        http_response_code(403);
        exit('Access denied.');
    }

    return $user;
}

function update_user_title(mysqli $conn, int $userId): void
{
    $stmt = mysqli_prepare(
        $conn,
        'UPDATE users
         SET title = CASE
            WHEN points >= 20000 THEN "Maze Legend"
            WHEN points >= 15000 THEN "Shadow Sprinter"
            WHEN points >= 7000 THEN "Escape Master"
            WHEN points >= 2500 THEN "Maze Hunter"
            WHEN points >= 1000 THEN "Beginner Explorer"
            ELSE "New Player"
         END
         WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}

function update_last_seen(mysqli $conn, int $userId): void
{
    $stmt = mysqli_prepare($conn, 'UPDATE users SET last_seen = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}

function is_user_online(?string $lastSeen): bool
{
    if (!$lastSeen) {
        return false;
    }
    return strtotime($lastSeen) > (time() - 300);
}

function online_dot(?string $lastSeen): string
{
    $on    = is_user_online($lastSeen);
    $cls   = $on ? 'online-dot' : 'offline-dot';
    $title = $on ? 'Online' : 'Offline';
    return '<span class="' . $cls . '" title="' . $title . '"></span>';
}

function call_ml_api(string $endpoint): ?array
{
    $mlUrl = rtrim(getenv('ML_API_URL') ?: '', '/');
    if (!$mlUrl) {
        return null;
    }

    $ch = curl_init($mlUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);

    return ($response && $response !== false) ? json_decode($response, true) : null;
}

function ensure_default_achievements(mysqli $conn): void
{
    $defaults = [
        ['FIRST_ESCAPE', 'First Escape', 'Complete your first successful run.'],
        ['TEN_GAMES', 'Getting Started', 'Play 10 games in total.'],
        ['FIVE_WINS', 'Consistent Runner', 'Win 5 games.'],
        ['LEVEL_5', 'Deep Runner', 'Beat Level 5 or higher.'],
        ['POINTS_1000', 'Beginner Explorer', 'Reach 1000 total points.'],
        ['POINTS_2500', 'Maze Hunter', 'Reach 2500 total points.'],
        ['POINTS_7000', 'Escape Master', 'Reach 7000 total points.'],
        ['POINTS_15000', 'Shadow Sprinter', 'Reach 15000 total points.'],
        ['POINTS_20000', 'Maze Legend', 'Reach 20000 total points.'],
        ['KEY_COLLECTOR', 'Key Collector', 'Collect 25 keys across all runs.'],
        ['SPEED_40', 'Rapid Exit', 'Finish any level in under 40 seconds.'],
        ['HARD_CLEAR', 'No More Training Wheels', 'Beat Level 8.'],
    ];

    foreach ($defaults as $row) {
        $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO achievements (code, name, description) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sss', $row[0], $row[1], $row[2]);
        mysqli_stmt_execute($stmt);
    }
}

function unlock_achievement(mysqli $conn, int $userId, string $code): void
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM achievements WHERE code = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $achievement = mysqli_fetch_assoc($result);

    if (!$achievement) {
        return;
    }

    $achievementId = (int) $achievement['id'];
    $insert = mysqli_prepare($conn, 'INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)');
    mysqli_stmt_bind_param($insert, 'ii', $userId, $achievementId);
    mysqli_stmt_execute($insert);
}

function evaluate_achievements(mysqli $conn, int $userId, int $levelId, bool $won, int $keysCollected, int $timeMs): void
{
    $statsSql = 'SELECT
            COUNT(*) AS games_played,
            COALESCE(SUM(CASE WHEN won = 1 THEN 1 ELSE 0 END), 0) AS total_wins,
            COALESCE(SUM(keys_collected), 0) AS total_keys
        FROM scores
        WHERE user_id = ?';

    $stmt = mysqli_prepare($conn, $statsSql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($won) {
        unlock_achievement($conn, $userId, 'FIRST_ESCAPE');
    }
    if ((int) $stats['games_played'] >= 10) {
        unlock_achievement($conn, $userId, 'TEN_GAMES');
    }
    if ((int) $stats['total_wins'] >= 5) {
        unlock_achievement($conn, $userId, 'FIVE_WINS');
    }
    if ((int) $stats['total_keys'] >= 25) {
        unlock_achievement($conn, $userId, 'KEY_COLLECTOR');
    }
    if ($won && $timeMs > 0 && $timeMs < 40000) {
        unlock_achievement($conn, $userId, 'SPEED_40');
    }
    if ($won && $levelId >= 5) {
        unlock_achievement($conn, $userId, 'LEVEL_5');
    }
    if ($won && $levelId >= 8) {
        unlock_achievement($conn, $userId, 'HARD_CLEAR');
    }

    $user = current_user($conn);
    $points = $user ? (int) $user['points'] : 0;

    if ($points >= 1000) unlock_achievement($conn, $userId, 'POINTS_1000');
    if ($points >= 2500) unlock_achievement($conn, $userId, 'POINTS_2500');
    if ($points >= 7000) unlock_achievement($conn, $userId, 'POINTS_7000');
    if ($points >= 15000) unlock_achievement($conn, $userId, 'POINTS_15000');
    if ($points >= 20000) unlock_achievement($conn, $userId, 'POINTS_20000');
}
