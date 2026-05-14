<?php
require_once __DIR__ . '/../core/functions.php';
require_login();
$pageTitle = 'Challenge Mode';
$user = current_user($conn);
require_once __DIR__ . '/../core/header.php';
?>
<section class="game-header card">
    <div>
        <h1>Challenge Mode</h1>
        <p class="lead compact">
            Start from Level 1, progress in order, use lives carefully, and try to complete the full run.
        </p>
    </div>
    <div class="status-pills">
        <span class="pill">Lives: <strong id="hudLives">3</strong></span>
        <span class="pill">Points: <strong id="hudPoints"><?php echo (int) $user['points']; ?></strong></span>
        <span class="pill">Title: <strong id="hudTitle"><?php echo esc($user['title']); ?></strong></span>
    </div>
</section>

<section class="game-layout">
    <div class="card game-card">
        <div class="canvas-wrap">
            <canvas id="gameCanvas" width="960" height="640"></canvas>
            <div id="overlay" class="game-overlay hidden"></div>
        </div>
    </div>

    <aside class="card control-card">
        <h2>Challenge stats</h2>

        <div id="stats" class="stats-box"></div>

        <button id="startBtn" class="btn" type="button">Start Run</button>

        <div id="message" class="message-box">Press Start Run when ready.</div>
    </aside>
</section>

<script>
window.GAME_MODE = 'challenge';
</script>

<script id="currentUserData" type="application/json">
<?php
echo json_encode([
    'id' => (int) $user['id'],
    'points' => (int) $user['points'],
    'wins' => (int) $user['wins'],
    'title' => $user['title'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
</script>

<script type="module" src="<?php echo esc(app_url('js/game-page.js')); ?>"></script>
<?php require_once __DIR__ . '/../core/footer.php'; ?>