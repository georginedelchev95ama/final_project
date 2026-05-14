<?php
require_once __DIR__ . '/../core/functions.php';
$pageTitle = 'Leaderboard';
$type = $_GET['view'] ?? 'points';

$pointsResult = mysqli_query($conn, "
    SELECT username, title, points, wins, games_played, last_seen
    FROM users
    ORDER BY points DESC, wins DESC, username ASC
    LIMIT 50
");

$levelResult = mysqli_query($conn, "
    SELECT
        u.username,
        u.last_seen,
        s.level_id AS best_level,
        MIN(s.time_ms) AS best_time
    FROM scores s
    JOIN users u ON u.id = s.user_id
    WHERE s.won = 1
      AND s.level_id = (
          SELECT MAX(s2.level_id)
          FROM scores s2
          WHERE s2.user_id = s.user_id
            AND s2.won = 1
      )
    GROUP BY s.user_id, u.username, s.level_id, u.last_seen
    ORDER BY best_level DESC, best_time ASC
    LIMIT 50
");

require_once __DIR__ . '/../core/header.php';
?>
<section class="card">
    <h1>&#127942; Leaderboard</h1>
    <div class="tabs">
        <a class="tab <?php echo $type === 'points' ? 'active' : ''; ?>" href="<?php echo esc(app_url('pages/leaderboard.php?view=points')); ?>">Points ranking</a>
        <a class="tab <?php echo $type === 'level' ? 'active' : ''; ?>" href="<?php echo esc(app_url('pages/leaderboard.php?view=level')); ?>">Level ranking</a>
    </div>

    <?php if ($type === 'points'): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>User</th><th>Title</th><th>Points</th><th>Wins</th><th>Games</th></tr>
                </thead>
                <tbody>
                    <?php $rank = 1; while ($row = mysqli_fetch_assoc($pointsResult)): ?>
                        <tr>
                            <td><?php
                                if ($rank === 1) echo '&#129351;';
                                elseif ($rank === 2) echo '&#129352;';
                                elseif ($rank === 3) echo '&#129353;';
                                else echo $rank;
                                $rank++;
                            ?></td>
                            <td class="lb-user">
                                <?php if (is_user_online($row['last_seen'] ?? null)): ?>
                                    <span class="online-dot"></span>
                                <?php endif; ?>
                                <a href="<?php echo esc(app_url('pages/user.php?username=' . urlencode($row['username']))); ?>"
                                   style="color:inherit;text-decoration:none">
                                    <?php echo esc($row['username']); ?>
                                </a>
                            </td>
                            <td class="lb-title"><?php echo esc($row['title']); ?></td>
                            <td><?php echo (int) $row['points']; ?></td>
                            <td><?php echo (int) $row['wins']; ?></td>
                            <td><?php echo (int) $row['games_played']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>User</th><th>Best level completed</th><th>Best time</th></tr>
                </thead>
                <tbody>
                    <?php $rank = 1; while ($row = mysqli_fetch_assoc($levelResult)): ?>
                        <tr>
                            <td><?php
                                if ($rank === 1) echo '&#129351;';
                                elseif ($rank === 2) echo '&#129352;';
                                elseif ($rank === 3) echo '&#129353;';
                                else echo $rank;
                                $rank++;
                            ?></td>
                            <td class="lb-user">
                                <?php if (is_user_online($row['last_seen'] ?? null)): ?>
                                    <span class="online-dot"></span>
                                <?php endif; ?>
                                <a href="<?php echo esc(app_url('pages/user.php?username=' . urlencode($row['username']))); ?>"
                                   style="color:inherit;text-decoration:none">
                                    <?php echo esc($row['username']); ?>
                                </a>
                            </td>
                            <td><?php echo (int) $row['best_level']; ?></td>
                            <td><?php echo number_format($row['best_time'] / 1000, 2); ?>s</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>
