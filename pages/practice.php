<?php
require_once __DIR__ . '/../core/functions.php';
require_login();
$pageTitle = 'Practice Mode';
$user = current_user($conn);
require_once __DIR__ . '/../core/header.php';
?>
<section class="game-header card">
    <div>
        <h1>Practice Mode</h1>
        <p class="lead compact">
            Choose any level and play. Collect every key, then reach the exit while avoiding enemies.
        </p>
    </div>

    <div class="status-pills">
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
        <h2>Practice controls</h2>

        <div class="panel-box">
            <label for="levelSelect">Level</label>
            <select id="levelSelect"></select>
        </div>

        <button id="startBtn" class="btn" type="button">Start Level</button>

        <div class="panel-box">
            <h3>How it works</h3>
            <p>Move with arrow keys or WASD. Collect every key, then reach the exit door.</p>
            <p>Enemies use maze pathfinding and will go around walls to catch you.</p>
        </div>

        <div id="message" class="message-box">Choose a level and press Start Level.</div>
        <div id="stats" class="stats-box"></div>
    </aside>
</section>

<script>
window.GAME_MODE = 'practice';
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