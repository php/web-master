<?php
// $Id$

require_once 'cvs-auth.inc';

$user = $pw = null;

// Protect from register_globals vulnerability
if (isset($_GET['_SERVER']) || isset($_POST['_SERVER']) || isset($_COOKIE['_SERVER'])) {
    die("ERR: Permission denied.");
}
if ($_SERVER['REMOTE_ADDR'] != '65.75.184.90' &&
    md5($_GET['token']) != 'e6bb2b8f864bcef96c4608d49aa76596') {
    die("ERR: Permission denied.");
}

if (isset($_GET['credential'])) {
    list($user, $pw) = explode(":", base64_decode($_GET['credential']));
} else {
    die("ERR: No credential passed.");
}

if (verify_password($user, $pw)) {
    die("OK");
} else {
    die("ERR: Login failed.");
}
