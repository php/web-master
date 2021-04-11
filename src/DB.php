<?php

namespace App;

use PDO;

final class DB extends PDO {
    public static function connect() {
        $dbh = new self('mysql:host=localhost;dbname=phpmasterdb', 'nobody', '');
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }
}