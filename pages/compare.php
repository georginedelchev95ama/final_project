<?php
require_once __DIR__ . '/../core/functions.php';

$nameA = trim($_GET['a'] ?? '');
$nameB = trim($_GET['b'] ?? '');

if ($nameA === '' || $nameB === '' || $nameA === $nameB) {
    header('Location: ' . app_url('pages/leaderboard.php'));
    exit();
}

function fetch_compare_user(mysqli $conn, string $username): ?array
{
    $stmt = mysqli_prepare($conn, '
        SELECT u.id, u.username, u.title, u.points, u.wins, u.games_played, u.best_level, u.last_seen,
               COALESCE(SUM(s.keys_collected), 0) AS total_keys,
               COALESCE(AVG(CASE WHEN s.won = 1 THEN s.time_ms END), 0) AS avg_win_time,
               COUNT(ua.id) AS achievement_count
        FROM users u
        LEFT JOIN scores s ON s.user_id = u.id
        LEFT JOIN user_achievements ua ON ua.user_id = u.id
        WHERE u.username = ?
        GROUP BY u.id
        LIMIT 1
    ');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) return null;

    $row['win_rate'] = $row['games_played'] > 0
        ? round(($row['wins'] / $row['games_played']) * 100, 1)
        : 0;

    return $row;
}

$userA = fetch_compare_user($conn, $nameA);
$userB = fetch_compare_user($conn, $nameB);

if (!$userA || !$userB) {
    header('Location: ' . app_url('pages/leaderboard.php'));
    exit();
}

$pageTitle = $userA['username'] . ' vs ' . $userB['username'];

function winner(mixed $a, mixed $b, bool $lowerWins = false): array
{
    if ($a == $b) return ['', ''];
    if ($lowerWins) {
        return $a < $b ? ['win', 'lose'] : ['lose', 'win'];
    }
    return $a > $b ? ['win', 'lose'] : ['lose', 'win'];
}

require_once __DIR__ . '/../core/header.php';
?>

<section class="card" style="margin-bottom:20px">
    <h1 style="font-size:1.8rem;text-align:center">
        <a href="<?php echo esc(app_url('pages/user.php?username=' . urlencode($userA['username']))); ?>"
           style="color:#ff1493;text-decoration:none"><?php echo esc($userA['username']); ?></a>
        &nbsp;<span style="opacity:.5">vs</span>&nbsp;
        <a href="<?php echo esc(app_url('pages/user.php?username=' . urlencode($userB['username']))); ?>"
           style="color:#00ffff;text-decoration:none"><?php echo esc($userB['username']); ?></a>
    </h1>
    <p class="muted" style="text-align:center">Head-to-head stat comparison</p>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="compare-table">
            <thead>
                <tr>
                    <th style="color:#ff1493">
                        <?php echo esc($userA['username']); ?>
                        <?php if (is_user_online($userA['last_seen'] ?? null)): ?>
                            <span class="online-dot"></span>
                        <?php endif; ?>
                    </th>
                    <th style="text-align:center;color:#fff;opacity:.6">Stat</th>
                    <th style="color:#00ffff;text-align:right">
                        <?php if (is_user_online($userB['last_seen'] ?? null)): ?>
                            <span class="online-dot"></span>
                        <?php endif; ?>
                        <?php echo esc($userB['username']); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = [
                    ['Points',       number_format($userA['points']),    number_format($userB['points']),    winner($userA['points'],          $userB['points'])],
                    ['Win Rate',     $userA['win_rate'] . '%',           $userB['win_rate'] . '%',           winner($userA['win_rate'],         $userB['win_rate'])],
                    ['Wins',         $userA['wins'],                     $userB['wins'],                     winner($userA['wins'],             $userB['wins'])],
                    ['Games Played', $userA['games_played'],             $userB['games_played'],             winner($userA['games_played'],     $userB['games_played'])],
                    ['Best Level',   $userA['best_level'],               $userB['best_level'],               winner($userA['best_level'],       $userB['best_level'])],
                    ['Keys Collected',(int)$userA['total_keys'],         (int)$userB['total_keys'],          winner($userA['total_keys'],       $userB['total_keys'])],
                    ['Achievements', $userA['achievement_count'],        $userB['achievement_count'],        winner($userA['achievement_count'],$userB['achievement_count'])],
                    ['Avg Win Time', $userA['avg_win_time'] > 0 ? number_format($userA['avg_win_time']/1000,2).'s' : '—',
                                     $userB['avg_win_time'] > 0 ? number_format($userB['avg_win_time']/1000,2).'s' : '—',
                                     winner($userA['avg_win_time'], $userB['avg_win_time'], true)],
                    ['Title',        $userA['title'],                    $userB['title'],                    ['','']],
                ];
                foreach ($rows as [$label, $valA, $valB, $result]):
                ?>
                <tr>
                    <td class="compare-val <?php echo $result[0]; ?>"><?php echo esc((string)$valA); ?></td>
                    <td class="compare-label"><?php echo esc($label); ?></td>
                    <td class="compare-val right <?php echo $result[1]; ?>"><?php echo esc((string)$valB); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top:16px;font-size:.85rem">
        <span style="color:#00ff88">Green</span> = leading stat &nbsp;|&nbsp; Avg Win Time: lower is better.
    </p>
</section>

<?php require_once __DIR__ . '/../core/footer.php'; ?>
