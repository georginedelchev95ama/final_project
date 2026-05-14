<?php
require_once __DIR__ . '/../core/functions.php';
redirect_if_logged_in('pages/index.php');
$pageTitle = 'Login';
require_once __DIR__ . '/../core/header.php';
?>
<div class="form-wrap">
    <section class="card form-card">
        <h1>&#128272; Login</h1>
        <p class="muted">Continue your progress, points, and achievements.</p>
        <form action="<?php echo esc(app_url('auth/process_login.php')); ?>" method="post" class="stack-form">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required>
            <label for="password">Password</label>
            <div class="pass-wrap">
                <input id="password" name="password" type="password" required>
                <button type="button" class="pass-toggle" aria-label="Toggle password visibility" onclick="togglePass('password', this)">&#128065;</button>
            </div>
            <button class="btn" type="submit">Login</button>
        </form>
        <p class="form-meta">No account yet? <a href="<?php echo esc(app_url('pages/register.php')); ?>">Register here</a>.</p>
    </section>
</div>
<script>
function togglePass(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; btn.classList.add('active'); }
    else { input.type = 'password'; btn.classList.remove('active'); }
}
</script>
<?php require_once __DIR__ . '/../core/footer.php'; ?>