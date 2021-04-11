<?php

use App\DB;

function gen_pass($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password(DB $db, $user, $pass) {
    $stmt = $db->prepare("SELECT svnpasswd FROM users WHERE cvsaccess AND username = ?");
    $stmt->execute([$user]);
    if (false === $row = $stmt->fetch()) {
        return false;
    }
    return password_verify($pass, $row['svnpasswd']);
}

function verify_username(DB $db, $user) {
    $stmt = $db->prepare("SELECT 1 FROM users WHERE cvsaccess AND username = ?");
    $stmt->execute([$user]);
    return $stmt->fetch() !== false;
}
