<?php

use App\DB;

function verify_username(DB $db, $user) {
    $stmt = $db->prepare("SELECT 1 FROM users WHERE cvsaccess AND username = ?");
    $stmt->execute([$user]);
    return $stmt->fetch() !== false;
}
