<?php

use App\Access;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../include/login.inc';

if (!Access::isMirrorSiteAdmin($cuser)) {
    warn("Sorry, you are not allowed to view this web page");
    exit;
}

phpinfo();