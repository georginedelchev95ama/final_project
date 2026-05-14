<?php
require_once __DIR__ . '/../core/functions.php';
require_login();
$pageTitle = 'Play';
$pageBodyClass = 'page-lift';
require_once __DIR__ . '/../core/header.php';
?>
<section class="card">
    <span class="eyebrow">Choose your mode</span>
    <h1>Play Maze Escape</h1>
    <p class="lead">Practice Mode lets you choose any level. Challenge Mode starts from Level 1 and 3 lives. Each completed level awards you with a new life. </p>
</section>

<section class="grid two-col">
      <article class="card">
        <h2>Challenge Mode</h2>
        <p>Start from Level 1, use lives carefully, and try to complete the full run.</p>
        <div class="button-row">
            <a class="btn" href="<?php echo esc(app_url('pages/challenge.php')); ?>">Open Challenge Mode</a>
        </div>
    </article>
    <article class="card">
        <h2>Practice Mode</h2>
        <p>Pick any level from the list and practise freely, unlimited attempts.</p>
        <div class="button-row">
            <a class="btn secondary" href="<?php echo esc(app_url('pages/practice.php')); ?>">Open Practice Mode</a>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>