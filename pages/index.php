<?php
$pageTitle = 'Maze Escape';
$pageBodyClass = 'page-lift';
require_once __DIR__ . '/../core/header.php';
$user = current_user($conn);
?>
<section class="hero-panel">
    <div>
        <span class="eyebrow">Arcade strategy challenge</span>
        <h1>Escape the maze, collect keys, and climb the leaderboard.</h1>
        <p class="lead">
            Play through maze levels, test routes in Practice Mode, survive sequential runs in Challenge Mode,
            earn points, unlock achievements, and improve your best results.
        </p>

        <div class="button-row">
            <a class="btn" href="<?php echo esc(app_url('pages/play.php')); ?>">Play now</a>

            <?php if (!$user): ?>
                <a class="btn secondary" href="<?php echo esc(app_url('pages/login.php')); ?>">Login</a>
                <a class="btn ghost" href="<?php echo esc(app_url('pages/register.php')); ?>">Create account</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero-card card">
        <h3>Game systems</h3>
        <ul class="feature-list">
            <li>Practice Mode for practicing any level</li>
            <li>Challenge Mode for points and titles</li>
            <li>Achievements and leaderboard</li>
            <li>Enemy pathfinding and key collection</li>
        </ul>
    </div>
</section>

<section class="grid two-col">
    <article class="card">
        <h2>Challenge Mode</h2>
        <p>
            Challenge Mode starts from Level 1 and uses lives. If you get caught, you lose a life.
            If you run out of lives starts again from Level 1.
        </p>
    </article>
    <article class="card">
        <h2>Practice Mode</h2>
        <p>
            Use Practice Mode to choose any level from the dropdown menu and improve yourself.
            
        </p>
    </article>

</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>