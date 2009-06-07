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
    echo json_encode(array("error" => $text));
    exit;
}

(!isset($token) || md5($token) != "d3fbcabfcf3648095037175fdeef322f") && error("token not correct.", 401);

$USERNAME = filter_input(INPUT_GET, "username", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

$pdo = new PDO("mysql:host=localhost;dbname=phpmasterdb", "nobody", "");

$stmt = $pdo->prepare("SELECT userid, name, email, username, spamprotect, use_sa, greylist, enable FROM users WHERE username = ? AND cvsaccess LIMIT 1");
if (!$stmt->execute(array($USERNAME))) {
    error("This error should never happen", 500);
}

$results = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$results) {
    error("No such user", 404);
}

$stmt = $pdo->prepare("SELECT note, entered FROM users_note WHERE userid = ?");
if (!$stmt->execute(array($results["userid"]))) {
    error("This error should never happen", 500);
}

unset($results["userid"]); // Our internal ID has no meaning for anyone
$results["notes"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);

