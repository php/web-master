<?php // vim: et ts=4 sw=4
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
    echo json_encode(["error" => $text]);
    exit;
}

(!isset($_GET['token']) || md5($_GET['token']) != "d3fbcabfcf3648095037175fdeef322f") && error("token not correct.", 401);

$pdo = new PDO("mysql:host=localhost;dbname=phpmasterdb", "nobody", "");

$stmt = $pdo->prepare("SELECT name, username FROM users WHERE enable AND cvsaccess");
if (!$stmt->execute()) {
    error("This error should never happen", 500);
}

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$results) {
    error("This should never happen either", 404);
}

echo json_encode($results);


