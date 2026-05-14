<?php
require_once __DIR__ . '/../core/functions.php';
redirect_if_logged_in('pages/index.php');
$pageTitle = 'Register';
require_once __DIR__ . '/../core/header.php';
?>
<div class="form-wrap">
    <section class="card form-card">
        <h1>&#127918; Create account</h1>
        <p class="muted">Track scores, points, titles, and achievements.</p>
        <form action="<?php echo esc(app_url('auth/process_register.php')); ?>" method="post" class="stack-form">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="50">
            <label for="password">Password</label>
            <div class="pass-wrap">
                <input id="password" name="password" type="password" required minlength="6">
                <button type="button" class="pass-toggle" aria-label="Toggle password visibility" onclick="togglePass('password', this)">&#128065;</button>
            </div>
            <label for="repeat_password">Repeat password</label>
            <div class="pass-wrap">
                <input id="repeat_password" name="repeat_password" type="password" required minlength="6">
                <button type="button" class="pass-toggle" aria-label="Toggle password visibility" onclick="togglePass('repeat_password', this)">&#128065;</button>
            </div>
            <button class="btn" type="submit">Create account</button>
        </form>
        <p class="form-meta">Already registered? <a href="<?php echo esc(app_url('pages/login.php')); ?>">Login here</a>.</p>
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