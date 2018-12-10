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

$s = file_get_contents("https://master.php.net/fetch/cvsauth.php", false, $ctx);

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

require 'functions.inc';
require 'cvs-auth.inc';

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

$MQ = get_magic_quotes_gpc();

// Create required variables and kill MQ
$fields = ["token", "username", "password"];
foreach($fields as $field) {
	if (isset($_POST[$field])) {
		$$field = $MQ ? stripslashes($_POST[$field]) : $_POST[$field];
	} else {
		exit_forbidden(E_UNKNOWN);
	}
}

# token required since this should only get accessed from .php.net sites
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "73864a7c89d97a13368fc213075036d1") {
	exit_forbidden(E_UNKNOWN);
}

if (!verify_username($username)) {
	exit_forbidden(E_USERNAME);
}

if (!verify_password($username, $password)) {
	exit_forbidden(E_PASSWORD);
}

exit_success();


