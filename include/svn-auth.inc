<?php
define("REALM", "PHP Subversion Repository");

function gen_svn_pass($username, $password) {
	return md5(sprintf("%s:%s:%s", $username, REALM, $password))
}

