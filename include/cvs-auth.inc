<?php
/* $Id$ */

define("LOGIN_IGNORE_PWD", FALSE);
define("LOGIN_PWD_FILE",   "/repository/CVSROOT/passwd");

function find_password($user)
{
    if (LOGIN_IGNORE_PWD) return " "; // should be at least one char
    $fp = fopen(LOGIN_PWD_FILE, "r");
    if (!$fp) {
        die("ERROR: Unable to open CVS users file!");
    }
    while(!feof($fp)) {
        $line = fgets($fp, 120);
        list($luser, $passwd, $junk) = explode(":", $line);
        if ($user == $luser) {
            fclose($fp);
            return $passwd;
        }
    }
    fclose($fp);
    return "";
}

function verify_password($user, $pass)
{
    $psw = find_password($user);
    if (strlen($psw) > 0) {
        if (LOGIN_IGNORE_PWD || crypt($pass, substr($psw, 0, 2)) == $psw) {
            return TRUE;
        }
    }
    return FALSE;
}
