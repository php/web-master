<?php // vim: et ts=4 sw=4
/* $Id$ */


define("REALM", "PHP Subversion Repository");

function gen_legacy_svn_pass($username, $password) {
	  return md5(sprintf("%s:%s:%s", $username, REALM, $password));
}

function gen_pass($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($user, $pass)
{
    db_connect();

    $res = db_query_safe("SELECT svnpasswd FROM users WHERE cvsaccess AND username = ?", [$user]);

    if ($res && mysql_num_rows($res) == 1) {
        $row = mysql_fetch_array($res);
        if (strlen($row['svnpasswd']) == 32) {
            // Legacy md5 password.
            if (gen_legacy_svn_pass($user, $pass) !== $row["svnpasswd"]) {
                return false;
            }

            // Upgrade to new password hash.
            $newHash = gen_pass($pass);
            db_query_safe("UPDATE users SET svnpasswd = ? WHERE username = ?", [$newHash, $user]);
            return true;
        }

        return password_verify($pass, $row['svnpasswd']);
    }

    return false;
}

function verify_username($user) {
    db_connect();
    $res = db_query_safe("SELECT 1 FROM users WHERE cvsaccess AND username = ?", [$user]);
    return $res && mysql_num_rows($res) == 1;
}
