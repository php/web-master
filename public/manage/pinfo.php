<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../include/login.inc';

if (!is_mirror_site_admin($cuser)) {
    warn("Sorry, you are not allowed to view this web page");
    exit;
}

phpinfo();