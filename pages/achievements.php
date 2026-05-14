<?php
require_once __DIR__ . '/../core/functions.php';
ensure_default_achievements($conn);

$pageTitle = 'Achievements';
$userId = is_logged_in() ? (int) $_SESSION['user_id'] : 0;

$sql = 'SELECT a.id, a.code, a.name, a.description,
               MAX(CASE WHEN ua.user_id IS NOT NULL THEN 1 ELSE 0 END) AS unlocked,
               MAX(ua.unlocked_at) AS unlocked_at
        FROM achievements a
        LEFT JOIN user_achievements ua
          ON ua.achievement_id = a.id AND ua.user_id = ?
        GROUP BY a.id, a.code, a.name, a.description
        ORDER BY unlocked DESC, unlocked_at DESC, a.name ASC';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$unlockedAchievements = [];
$lockedAchievements = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ((int) $row['unlocked'] === 1) {
        $unlockedAchievements[] = $row;
    } else {
        $lockedAchievements[] = $row;
    }
}

require_once __DIR__ . '/../core/header.php';
?>
<section class="card">
    <h1>Achievements</h1>
    <p class="lead compact">Unlock progression milestones, speed badges, deep-run achievements, and point-based titles.</p>
</section>

<section class="card">
    <h2>Unlocked achievements</h2>
    <?php if (count($unlockedAchievements) > 0): ?>
        <div class="achievement-list">
            <?php foreach ($unlockedAchievements as $achievement): ?>
                <article class="achievement-item unlocked">
                    <h3><?php echo esc($achievement['name']); ?></h3>
                    <p><?php echo esc($achievement['description']); ?></p>
                    <small>Unlocked<?php if (!empty($achievement['unlocked_at'])): ?>: <?php echo esc($achievement['unlocked_at']); ?><?php endif; ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">No achievements unlocked yet.</p>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Locked achievements</h2>
    <?php if (count($lockedAchievements) > 0): ?>
        <div class="achievement-list">
            <?php foreach ($lockedAchievements as $achievement): ?>
                <article class="achievement-item locked">
                    <h3><?php echo esc($achievement['name']); ?></h3>
                    <p><?php echo esc($achievement['description']); ?></p>
                    <small>Locked</small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">All achievements unlocked.</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>