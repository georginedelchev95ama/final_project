<?php
require_once __DIR__ . '/core/functions.php';

$currentUser = current_user($conn);

echo "=== SESSION ===\n";
echo "session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n\n";

echo "=== CURRENT USER ===\n";
if ($currentUser) {
    echo "username: " . $currentUser['username'] . "\n";
    echo "games_played: " . $currentUser['games_played'] . "\n";
} else {
    echo "NOT LOGGED IN\n";
}

echo "\n=== ML_API_URL ===\n";
$mlUrl = getenv('ML_API_URL');
echo var_export($mlUrl, true) . "\n\n";

if ($currentUser && $mlUrl) {
    $result = call_ml_api('/api/recommend/' . urlencode($currentUser['username']));
    echo "=== ML RESULT ===\n";
    print_r($result);
}
