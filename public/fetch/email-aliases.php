<?php

use App\DB;

require __DIR__ . '/../../vendor/autoload.php';

function error($text, $status)
{
    switch((int)$status) {
    default:
    case 500:
        header("HTTP/1.0 500 Internal server error");
        break;

    case 404:
        header("HTTP/1.0 404 Not Found");
        break;

    case 401:
        header("HTTP/1.0 401 Unauthorized");
        break;
    }
    exit;
}

// original token defined in ansible vault and in fetch-aliases-from-main.sh script on php-smtp4:~/emailsync
(!isset($_GET['token']) || sha1($_GET['token']) != "1789734af16d0fe009375e1f4dbe11e02c5919bc") && error("token not correct.", 401);

$pdo = DB::connect();

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE enable = 1 AND email != '' ORDER BY username");
if (!$stmt->execute()) {
    error("This error should never happen", 500);
}

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$results) {
    error("This should never happen either", 404);
}

echo "username\temail\n";

foreach ($results as $result) {
    echo $result['username'], "\t", $result['email'], "\n";
}
