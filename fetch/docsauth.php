<?php
// $Id$

require_once 'cvs-auth.inc';

$user = null;
$pw = null;

if ($_SERVER['REMOTE_ADDR'] != '81.57.253.132' &&
	$_GET['token'] != '97669cf8c9a1449eb2099ea6147d125697669cf8c9a1449eb2099ea6147d1256') {
	die ("ERR: Permission denied.");
}

if (isset($_GET['credential'])) {
	list($user, $pw) = explode(":", base64_decode($_GET['credential']));
} else {
	die ("ERR: No credential passed.");
}

if (verify_password($user, $pw)) {
	die ("OK");
} else {
	die ("ERR: Login failed.");
}
