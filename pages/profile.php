<?php
require_once __DIR__ . '/../core/functions.php';
require_login();
$user = current_user($conn);
header('Location: ' . app_url('pages/user.php?username=' . urlencode($user['username'])));
exit();
