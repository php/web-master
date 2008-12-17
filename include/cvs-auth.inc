<?php // vim: et ts=4 sw=4
/* $Id$ */

define("LOGIN_IGNORE_PWD", FALSE);
define("LOGIN_PWD_FILE",   "/repository/CVSROOT/passwd");
require dirname(__FILE__) . "/svn-auth.inc";

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
    if (!LOGIN_IGNORE_PWD) {
        @mysql_connect("localhost","nobody","")
            or die("unable to connect to database");
        @mysql_select_db("phpmasterdb");

        $username = escape($user);
        $res = @mysql_query("SELECT md5passwd FROM users WHERE svnpasswd != '' AND md5passwd != '' AND cvsaccess AND username = '$username'");

        // There is a good chance that md5passwd is empty
        if ($res && mysql_num_rows($res) == 1) {
            $row = @mysql_fetch_array($res);
            return md5($pass) == $row["md5passwd"];
        }
    }
    if (verify_login(find_password($user), $pass)) {
        if (!LOGIN_IGNORE_PWD) {
            // Update the password to enable svn access
            $svnpasswd = gen_svn_pass($user, $pass);
            $md5passwd = md5($pass);

            $query = "UPDATE users SET svnpasswd='$svnpasswd', md5passwd='$md5passwd' WHERE username='$username'";
            mysql_query($query);
        }
        return TRUE;
    }
    return FALSE;
}

function verify_login($psw, $pass)
{
    if (strlen($psw) > 0) {
        if (LOGIN_IGNORE_PWD || crypt($pass, substr($psw, 0, 2)) == $psw) {
            return TRUE;
        }
    }
    return FALSE;
}

