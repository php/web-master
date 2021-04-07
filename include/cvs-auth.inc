<?php // vim: et ts=4 sw=4

function gen_pass($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($user, $pass)
{
    db_connect();

    $res = db_query_safe("SELECT svnpasswd FROM users WHERE cvsaccess AND username = ?", [$user]);

    if ($res && mysql_num_rows($res) == 1) {
        $row = mysql_fetch_array($res);
        return password_verify($pass, $row['svnpasswd']);
    }

    return false;
}

function verify_username($user) {
    db_connect();
    $res = db_query_safe("SELECT 1 FROM users WHERE cvsaccess AND username = ?", [$user]);
    return $res && mysql_num_rows($res) == 1;
}
