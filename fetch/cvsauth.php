<?php
/*
CVS username+password authentication service for .php.net sites.
Usage:
$post = http_build_query(
	array(
		"token" => getenv("TOKEN"),
		"username" => $username,
		"password" => $password,
	)
);

$opts = array(
	"method"  => "POST",
	"header"  => "Content-type: application/x-www-form-urlencoded",
	"content" => $post,
);

$ctx = stream_context_create(array("http" => $opts));

$s = file_get_contents("http://master.php.is/fetch/cvsauth.php", false, $ctx);

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

require 'cvs-auth.inc';

# Error constants
define("E_UNKNOWN", 0);
define("E_USERNAME", 1);
define("E_PASSWORD", 2);

function exit_forbidden($why) {
	switch($why) {
	case E_USERNAME:
		echo serialize(array("errstr" => "Incorrect username", "errno" => E_USERNAME));
		break;

	case E_PASSWORD:
		echo serialize(array("errstr" => "Incorrect password", "errno" => E_PASSWORD));
		break;

	case E_UNKNOWN:
	default:
		echo serialize(array("errstr" => "Unknown error", "errno" => E_UNKNOWN));
	}
	exit;
}

function exit_success() {
	echo serialize(array("SUCCESS" => "Username and password OK"));
	exit;
}


$MQ = false;
// FC for PHP5.3 && PHP6
if (function_exists("get_magic_quotes_gpc")) {
	$MQ = (bool) @get_magic_quotes_gpc();
}

// Create required variables and kill MQ
$fields = array("token", "username", "password");
foreach($fields as $field) {
	if (isset($_POST[$field])) {
		$$field = $MQ ? stripslashes($_POST[$field]) : $_POST[$field];
	} else {
		exit_forbidden(E_UNKNOWN);
	}
}

# token required since this should only get accessed from .php.net sites
if (md5($token) != "a37f2f560c173675e839b02a89ce8104") {
	exit_forbidden(E_UNKNOWN);
}

$pass = find_password($username);
if (strlen($pass) < 1) {
	exit_forbidden(E_USERNAME);
}

if (!verify_login($pass, $password)) {
	exit_forbidden(E_PASSWORD);
}

exit_success();


