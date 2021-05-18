<?php

namespace App\Security;

use App\DB;

class Password
{
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verify($username, $password)
    {
        $db = DB::connect();
        
        $stmt = $db->prepare("SELECT svnpasswd FROM users WHERE cvsaccess AND username = ?");
        $stmt->execute([$username]);
        if (false === $row = $stmt->fetch()) {
            return false;
        }
        return password_verify($password, $row['svnpasswd']);
    }
}