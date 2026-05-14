<?php
require_once __DIR__ . '/../core/functions.php';
require_login();
$pageTitle = 'Profile';
$user = current_user($conn);

$achievementSql = 'SELECT a.name, a.description, ua.unlocked_at
    FROM user_achievements ua
    JOIN achievements a ON a.id = ua.achievement_id
    WHERE ua.user_id = ?
    ORDER BY ua.unlocked_at DESC, a.name ASC';

$stmt = mysqli_prepare($conn, $achievementSql);
$userId = (int) $_SESSION['user_id'];
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$achievementResult = mysqli_stmt_get_result($stmt);

require_once __DIR__ . '/../core/header.php';
?>
<section class="grid two-col">
    <article class="card">
        <h1><?php echo esc($user['username']); ?></h1>
        <p class="lead compact">
            Current title: <strong><?php echo esc($user['title']); ?></strong>
        </p>

        <div class="stats-grid">
            <div class="metric"><span>Points</span><strong><?php echo (int) $user['points']; ?></strong></div>
            <div class="metric"><span>Wins</span><strong><?php echo (int) $user['wins']; ?></strong></div>
            <div class="metric"><span>Games Played</span><strong><?php echo (int) $user['games_played']; ?></strong></div>
            <div class="metric"><span>Best Level</span><strong><?php echo (int) $user['best_level']; ?></strong></div>
        </div>

        <?php if (is_admin_user($user)): ?>
            <div class="button-row">
                <a class="btn" href="<?php echo esc(app_url('admin/index.php')); ?>">Open Admin Panel</a>
            </div>
        <?php endif; ?>
    </article>

    <article class="card">
        <h2>Progress</h2>
        <p>Titles update automatically from total points. Achievements unlock from gameplay milestones and performance.</p>
    </article>
</section>

<section class="card">
    <h2>Unlocked achievements</h2>

    <div class="achievement-list">
        <?php if (mysqli_num_rows($achievementResult) === 0): ?>
            <p class="muted">No achievements unlocked yet.</p>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($achievementResult)): ?>
                <article class="achievement-item unlocked">
                    <h3><?php echo esc($row['name']); ?></h3>
                    <p><?php echo esc($row['description']); ?></p>
                    <small>Unlocked: <?php echo esc($row['unlocked_at']); ?></small>
                </article>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>