<?php

declare(strict_types=1);

/*
Revised code by Dominick Lee
Original code derived from "Run your own PDO PHP class" by Philip Brown
Modernized for PHP 8.5
*/

class Database
{
    private ?PDO $dbh = null;
    private ?PDOStatement $stmt = null;

    public function __construct(
        private readonly string $host = DB_HOST,
        private readonly string $user = DB_USER,
        private readonly string $pass = DB_PASS,
        private readonly string $dbname = DB_NAME,
    ) {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // In PHP 8+ PDO already throws on connection failure by default,
            // but we re-throw as a RuntimeException so callers don't need
            // to know about PDO internals, and we never leave $dbh null.
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function query(string $query): void
    {
        $this->stmt = $this->dbh?->prepare($query) ?: null;
    }

    public function bind(string $param, mixed $value, ?int $type = null): void
    {
        $type ??= match (true) {
            is_int($value)  => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default          => PDO::PARAM_STR,
        };

        $this->stmt?->bindValue($param, $value, $type);
    }

    public function execute(): bool
    {
        return $this->stmt?->execute() ?? false;
    }

    public function resultset(): array
    {
        $this->execute();
        return $this->stmt?->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function single(): array|false
    {
        $this->execute();
        return $this->stmt?->fetch(PDO::FETCH_ASSOC) ?? false;
    }

    public function rowCount(): int
    {
        return $this->stmt?->rowCount() ?? 0;
    }

    public function lastInsertId(): string|false
    {
        return $this->dbh?->lastInsertId() ?? false;
    }

    public function beginTransaction(): bool
    {
        return $this->dbh?->beginTransaction() ?? false;
    }

    public function endTransaction(): bool
    {
        return $this->dbh?->commit() ?? false;
    }

    public function cancelTransaction(): bool
    {
        return $this->dbh?->rollBack() ?? false;
    }

    public function debugDumpParams(): void
    {
        $this->stmt?->debugDumpParams();
    }

    public function close(): bool
    {
        $this->stmt = null;
        $this->dbh = null;

        return true;
    }
}
