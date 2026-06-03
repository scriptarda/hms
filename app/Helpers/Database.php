<?php
namespace App\Helpers;

/**
 * PDO Database Wrapper
 * Singleton pattern with prepared statements only
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct(array $config)
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    /**
     * Initialize the database connection
     */
    public static function init(array $config): void
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
    }

    /**
     * Get the database instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$instance;
    }

    /**
     * Get the PDO instance
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return the statement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public function fetch(string $sql, array $params = []): ?object
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a row and return the last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    /**
     * Soft delete a row
     */
    public function softDelete(string $table, string $where, array $params = []): int
    {
        $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Count rows
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where} AND deleted_at IS NULL";
        return (int) $this->fetchColumn($sql, $params);
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Get the last insert ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Test a database connection with given config
     */
    public static function testConnection(array $config): bool
    {
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%d;charset=%s',
                $config['driver'] ?? 'mysql',
                $config['host'],
                $config['port'] ?? 3306,
                $config['charset'] ?? 'utf8mb4'
            );
            new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
