<?php

namespace App;

use PDO;

final class DB extends PDO
{
    public static function connect(): self
    {
        $connectionConfig = 'mysql:host=' . self::getHost() . ';port=' . self::getPort() . ';dbname=' . self::getDatabase();

        $db = new self($connectionConfig, self::getUser(), self::getPassword());
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $db;
    }

    public static function getHost(): string
    {
        return \getenv("DATABASE_HOST") ?: "localhost";
    }

    public static function getPort(): string
    {
        return \getenv("DATABASE_PORT") ?: "3306";
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

    public function safeQuery(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function safeQueryReturnsAffectedRows(string $sql, array $params = []): int
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function row(string $sql, array $params = []): array
    {
        $result = $this->safeQuery($sql, $params);
        return array_shift($result) ?? [];
    }

    public function single(string $sql, array $params = [], $column = 0)
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }
}
