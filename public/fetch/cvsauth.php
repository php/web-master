<?php
/*
CVS username+password authentication service for .php.net sites.
Usage:
$post = http_build_query(
	[
		"token" => getenv("TOKEN"),
		"username" => $username,
		"password" => $password,
	]
);

$opts = [
	"method"  => "POST",
	"header"  => "Content-type: application/x-www-form-urlencoded",
	"content" => $post,
];

$ctx = stream_context_create(["http" => $opts]);

$s = file_get_contents("https://main.php.net/fetch/cvsauth.php", false, $ctx);

$a = @unserialize($s);
if (!is_array($a)) {
	echo "Unknown error\n";
	exit;
}
if (isset($a["errno"])) {
	echo "Authentication failed: ", $a["errstr"], "\n";
	exit;
}

echo $a["SUCCESS"], "\n";
*/

use App\DB;
use App\Security\Password;

require_once __DIR__.'/../../vendor/autoload.php';

# Error constants
define("E_UNKNOWN", 0);
define("E_USERNAME", 1);
define("E_PASSWORD", 2);

function exit_forbidden($why) {
	switch($why) {
	case E_USERNAME:
		echo serialize(["errstr" => "Incorrect username", "errno" => E_USERNAME]);
		break;

	case E_PASSWORD:
		echo serialize(["errstr" => "Incorrect password", "errno" => E_PASSWORD]);
		break;

	case E_UNKNOWN:
	default:
		echo serialize(["errstr" => "Unknown error", "errno" => E_UNKNOWN]);
	}
	exit;
}

function exit_success() {
	echo serialize(["SUCCESS" => "Username and password OK"]);
	exit;
}

function is_valid_cvsauth_token($token) {
    $hash = sha1($token);
    return $hash === 'c3d7b24474fc689f7144bb5c2fd403d939634b7e' // bugs.php.net
        || $hash === 'd4d4d68b78dc80fff48967ce8dc67e74bb87e903' // wiki.php.net
        || $hash === 'e201419bb48da4d427eb67e5f3fd108506360e89' // edit.php.net
        ;
}

// Create required variables
if (!isset($_POST['token']) || !isset($_POST['username']) || !isset($_POST['password'])) {
    exit_forbidden(E_UNKNOWN);
}

$token = $_POST['token'];
$username = $_POST['username'];
$password = $_POST['password'];

if (!is_valid_cvsauth_token($token)) {
	exit_forbidden(E_UNKNOWN);
}

$db = DB::connect();
if (!verify_username($db, $username)) {
	exit_forbidden(E_USERNAME);
}

if (!Password::verify($username, $password)) {
	exit_forbidden(E_PASSWORD);
}

exit_success();


