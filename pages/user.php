<?php
require_once __DIR__ . '/../core/functions.php';

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    header('Location: ' . app_url('pages/leaderboard.php'));
    exit();
}

$stmt = mysqli_prepare($conn, '
    SELECT id, username, title, points, wins, games_played, best_level, last_seen, created_at
    FROM users
    WHERE username = ?
    LIMIT 1
');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profileUser = mysqli_fetch_assoc($result);

if (!$profileUser) {
    header('Location: ' . app_url('pages/leaderboard.php'));
    exit();
}

$winRate = $profileUser['games_played'] > 0
    ? round(($profileUser['wins'] / $profileUser['games_played']) * 100, 1)
    : 0;

$scoresStmt = mysqli_prepare($conn, '
    SELECT s.level_id, l.name AS level_name, s.mode, s.time_ms, s.moves, s.won, s.points_earned, s.created_at
    FROM scores s
    JOIN levels l ON l.id = s.level_id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
    LIMIT 10
');
mysqli_stmt_bind_param($scoresStmt, 'i', $profileUser['id']);
mysqli_stmt_execute($scoresStmt);
$scoresResult = mysqli_stmt_get_result($scoresStmt);
$recentScores = [];
while ($row = mysqli_fetch_assoc($scoresResult)) {
    $recentScores[] = $row;
}

$totalKeysStmt = mysqli_prepare($conn, '
    SELECT COALESCE(SUM(keys_collected), 0) AS total_keys,
           COALESCE(AVG(CASE WHEN won = 1 THEN time_ms END), 0) AS avg_win_time
    FROM scores WHERE user_id = ?
');
mysqli_stmt_bind_param($totalKeysStmt, 'i', $profileUser['id']);
mysqli_stmt_execute($totalKeysStmt);
$extraStats = mysqli_fetch_assoc(mysqli_stmt_get_result($totalKeysStmt));

$achievementStmt = mysqli_prepare($conn, '
    SELECT a.name, a.description, ua.unlocked_at
    FROM user_achievements ua
    JOIN achievements a ON a.id = ua.achievement_id
    WHERE ua.user_id = ?
    ORDER BY ua.unlocked_at DESC, a.name ASC
');
mysqli_stmt_bind_param($achievementStmt, 'i', $profileUser['id']);
mysqli_stmt_execute($achievementStmt);
$achievementResult = mysqli_stmt_get_result($achievementStmt);
$achievements = [];
while ($row = mysqli_fetch_assoc($achievementResult)) {
    $achievements[] = $row;
}

$currentUser = current_user($conn);
$isOwnProfile = $currentUser && $currentUser['username'] === $profileUser['username'];
$isOnline = is_user_online($profileUser['last_seen'] ?? null);

$mlRecommendation = call_ml_api('/api/recommend/' . urlencode($profileUser['username']));

$pageTitle = $profileUser['username'] . ' — Profile';
require_once __DIR__ . '/../core/header.php';
?>

<section class="card profile-header-card">
    <div class="profile-top">
        <div class="profile-identity">
            <h1>
                <?php echo esc($profileUser['username']); ?>
                <?php if ($isOnline): ?>
                    <span class="online-dot" title="Online now"></span>
                <?php else: ?>
                    <span class="offline-dot" title="Offline"></span>
                <?php endif; ?>
            </h1>
            <p class="lead compact">
                <strong><?php echo esc($profileUser['title']); ?></strong>
                &nbsp;·&nbsp;
                Member since <?php echo date('M Y', strtotime($profileUser['created_at'])); ?>
                &nbsp;·&nbsp;
                <?php echo $isOnline ? '<span class="status-online">Online</span>' : '<span class="status-offline">Offline</span>'; ?>
            </p>
        </div>
        <?php if ($currentUser && !$isOwnProfile): ?>
        <div class="profile-actions">
            <button class="btn btn-primary"
                    onclick="window.chatOpenWith('<?php echo esc($profileUser['username']); ?>')">
                💬 Message
            </button>
            <a class="btn btn-secondary"
               href="<?php echo esc(app_url('pages/compare.php?a=' . urlencode($currentUser['username']) . '&b=' . urlencode($profileUser['username']))); ?>">
                ⚖️ Compare
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="grid two-col" style="margin-top:20px">
    <article class="card">
        <h2>Stats</h2>
        <div class="stats-grid">
            <div class="metric"><span>Points</span><strong><?php echo number_format($profileUser['points']); ?></strong></div>
            <div class="metric"><span>Wins</span><strong><?php echo (int) $profileUser['wins']; ?></strong></div>
            <div class="metric"><span>Win Rate</span><strong><?php echo $winRate; ?>%</strong></div>
            <div class="metric"><span>Games Played</span><strong><?php echo (int) $profileUser['games_played']; ?></strong></div>
            <div class="metric"><span>Best Level</span><strong><?php echo (int) $profileUser['best_level']; ?></strong></div>
            <div class="metric"><span>Keys Collected</span><strong><?php echo (int) $extraStats['total_keys']; ?></strong></div>
            <div class="metric"><span>Achievements</span><strong><?php echo count($achievements); ?></strong></div>
            <?php if ($extraStats['avg_win_time'] > 0): ?>
            <div class="metric"><span>Avg Win Time</span><strong><?php echo number_format($extraStats['avg_win_time'] / 1000, 1); ?>s</strong></div>
            <?php endif; ?>
        </div>
    </article>

    <article class="card">
        <h2>Compare with another player</h2>
        <p class="muted" style="margin-bottom:14px">Search a username to compare stats side by side.</p>
        <div class="compare-search-wrap">
            <input type="text" id="compare-search" class="form-control" placeholder="Search username…" autocomplete="off" />
            <div id="compare-suggestions" class="compare-suggestions" style="display:none"></div>
        </div>
        <?php if ($mlRecommendation && empty($mlRecommendation['error'])): ?>
        <div class="ml-tip" style="margin-top:20px">
            <span class="eyebrow">AI Insight</span>
            <?php if ($isOwnProfile): ?>
                <p><?php echo esc($mlRecommendation['reason'] ?? ''); ?></p>
                <?php if (!empty($mlRecommendation['recommended_level'])): ?>
                <a class="btn btn-secondary" href="<?php echo esc(app_url('pages/practice.php?level=' . (int)$mlRecommendation['recommended_level'])); ?>">
                    Practice Level <?php echo (int) $mlRecommendation['recommended_level']; ?> →
                </a>
                <?php endif; ?>
            <?php else: ?>
                <?php
                    $reason = $mlRecommendation['reason'] ?? '';
                    $reason = preg_replace('/\byou\b/i', esc($profileUser['username']), $reason);
                    $reason = preg_replace('/\byour\b/i', 'their', $reason);
                ?>
                <p><?php echo $reason; ?></p>
                <?php if (!empty($mlRecommendation['recommended_level'])): ?>
                <p class="muted" style="font-size:.85rem">Recommended focus: Level <?php echo (int) $mlRecommendation['recommended_level']; ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</div>

<section class="card" style="margin-top:20px">
    <h2>Recent runs</h2>
    <?php if (empty($recentScores)): ?>
        <p class="muted">No games played yet.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Mode</th>
                    <th>Result</th>
                    <th>Time</th>
                    <th>Moves</th>
                    <th>Points</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentScores as $score): ?>
                <tr>
                    <td><?php echo esc($score['level_name']); ?></td>
                    <td style="text-transform:capitalize"><?php echo esc($score['mode']); ?></td>
                    <td><?php echo $score['won'] ? '<span style="color:#00ffff">Win</span>' : '<span style="opacity:.6">Loss</span>'; ?></td>
                    <td><?php echo number_format($score['time_ms'] / 1000, 2); ?>s</td>
                    <td><?php echo (int) $score['moves']; ?></td>
                    <td><?php echo (int) $score['points_earned']; ?></td>
                    <td style="opacity:.7;font-size:.85rem"><?php echo date('d M y', strtotime($score['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<section class="card" style="margin-top:20px">
    <h2>Achievements <span style="opacity:.5;font-size:.8em">(<?php echo count($achievements); ?>)</span></h2>
    <div class="achievement-list">
        <?php if (empty($achievements)): ?>
            <p class="muted">No achievements unlocked yet.</p>
        <?php else: ?>
            <?php foreach ($achievements as $ach): ?>
                <article class="achievement-item unlocked">
                    <h3><?php echo esc($ach['name']); ?></h3>
                    <p><?php echo esc($ach['description']); ?></p>
                    <small>Unlocked <?php echo date('d M Y', strtotime($ach['unlocked_at'])); ?></small>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    const input = document.getElementById('compare-search');
    const box = document.getElementById('compare-suggestions');
    const profileUsername = <?php echo json_encode($profileUser['username']); ?>;
    let timer;

    if (!input) return;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 1) { box.style.display = 'none'; return; }
        timer = setTimeout(() => {
            fetch(window.APP_ROOT_URL + '/game/search_users.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(users => {
                    const filtered = users.filter(u => u !== profileUsername);
                    if (!filtered.length) { box.style.display = 'none'; return; }
                    box.innerHTML = filtered.map(u =>
                        `<div class="suggest-item" data-user="${u}">${u}</div>`
                    ).join('');
                    box.style.display = 'block';
                });
        }, 250);
    });

    box.addEventListener('click', function (e) {
        const item = e.target.closest('.suggest-item');
        if (!item) return;
        const other = item.dataset.user;
        window.location.href = window.APP_ROOT_URL + '/pages/compare.php?a=' +
            encodeURIComponent(profileUsername) + '&b=' + encodeURIComponent(other);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !box.contains(e.target)) {
            box.style.display = 'none';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../core/footer.php'; ?>
