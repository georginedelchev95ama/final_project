<?php
require_once __DIR__ . '/functions.php';
$user = current_user($conn);
$appRootUrl = app_url('');

if ($user) {
    update_last_seen($conn, (int) $user['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle ?? 'Maze Escape'); ?></title>
    <link rel="stylesheet" href="<?php echo esc(app_url('css/base.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/layout.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/forms.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/game.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/tables.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/admin.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc(app_url('css/chat.css')); ?>">
    <script>
        window.APP_ROOT_URL = <?php echo json_encode($appRootUrl, JSON_UNESCAPED_SLASHES); ?>;
        window.CURRENT_USERNAME = <?php echo $user ? json_encode($user['username']) : 'null'; ?>;
    </script>
</head>
<body class="<?php echo esc($pageBodyClass ?? ''); ?>">
<div class="site-shell">
    <header class="topbar">
        <a class="brand" href="<?php echo esc(app_url('pages/index.php')); ?>">🎮 Maze Escape</a>
        <nav class="nav-links">
            <a href="<?php echo esc(app_url('pages/index.php')); ?>">Home</a>
            <a href="<?php echo esc(app_url('pages/play.php')); ?>">Play</a>
            <a href="<?php echo esc(app_url('pages/leaderboard.php')); ?>">Leaderboard</a>
            <a href="<?php echo esc(app_url('pages/achievements.php')); ?>">Achievements</a>
            <a href="<?php echo esc(app_url('pages/help.php')); ?>">🤖 Help</a>
            <?php if ($user): ?>
                <a href="<?php echo esc(app_url('pages/profile.php')); ?>">Profile</a>
                <span class="nav-user">👤 <?php echo esc($user['username']); ?> · <?php echo esc($user['title']); ?></span>
                <a href="<?php echo esc(app_url('auth/logout.php')); ?>">Logout</a>
            <?php else: ?>
                <a href="<?php echo esc(app_url('pages/login.php')); ?>">Login</a>
                <a href="<?php echo esc(app_url('pages/register.php')); ?>">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="page">
        <?php render_flash(); ?>
