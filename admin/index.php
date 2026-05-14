<?php
require_once __DIR__ . '/../core/functions.php';
require_admin($conn);
$pageTitle = 'Admin Dashboard';
$pageBodyClass = 'page-lift';
require_once __DIR__ . '/../core/header.php';
?>
<section class="card">
    <h1>Admin Dashboard</h1>
    <p>Choose what you want to manage.</p>

    <div class="admin-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <a class="card admin-card" href="<?php echo esc(app_url('admin/achievements.php')); ?>">
            <h2>Achievements</h2>
            <p>Edit names and descriptions, or delete entries you do not want.</p>
        </a>

        <a class="card admin-card" href="<?php echo esc(app_url('admin/users.php')); ?>">
            <h2>Users</h2>
            <p>Manage users, best level, points, titles, and admin rights.</p>
        </a>
    </div>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>