<?php

namespace App;

use PDO;

final class DB extends PDO
{
    public static function connect(): self
    {
        $connectionConfig = 'mysql:host=' . self::getHost() . ';dbname=' . self::getDatabase();

        $db = new self($connectionConfig, self::getUser(), self::getPassword());
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;
    }

    public static function getHost(): string
    {
        return \getenv("DATABASE_HOST") ?: "localhost";
    }

    public static function getUser(): string
    {
        return \getenv("DATABASE_USER") ?: "nobody";
    }

    public static function getPassword(): string
    {
        return \getenv("DATABASE_PASSWORD") ?: "";
    }

    public static function getDatabase(): string
    {
        return \getenv("DATABASE_NAME") ?: "phpmasterdb";
    }
}