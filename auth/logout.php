<?php
require_once __DIR__ . '/../core/functions.php';
session_unset();
session_destroy();
session_start();
set_flash('success', 'You have been logged out.');
redirect_to('pages/index.php');
?>