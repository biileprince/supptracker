<?php

namespace App\Database\Turso;

use Illuminate\Support\Facades\Http;
use PDO;
use PDOStatement;

/**
 * A PDO-compatible wrapper for Turso HTTP API
 * Allows Laravel to use Turso database without requiring FFI extension
 */
class TursoPdoAdapter
{
    private string $url;
    private string $token;
    private array $attributes = [];

    public function __construct(string $url, string $token)
    {
        $this->url = $url;
        $this->token = $token;
        $this->attributes[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    }

    /**
     * Prepare and execute a SQL statement
     */
    public function prepare(string $sql, array $options = []): TursoStatement
    {
        return new TursoStatement($this->url, $this->token, $sql);
    }

    /**
     * Execute a SQL statement directly
     */
    public function exec(string $sql): int
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->post("{$this->url}/execute", [
                'statements' => [['q' => $sql]],
            ]);

            if (!$response->successful()) {
                throw new \PDOException("Query failed: " . $response->body());
            }

            return $response->json()['results'][0]['affected_rows'] ?? 0;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * Execute a query
     */
    public function query(string $sql, ?int $fetchMode = null): TursoStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Quote a string for use in a query
     */
    public function quote(string $string, int $type = PDO::PARAM_STR): string
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    /**
     * Get last inserted row ID
     */
    public function lastInsertId(?string $name = null): string
    {
        return "0";
    }

    /**
     * Get transaction status
     */
    public function inTransaction(): bool
    {
        return false;
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return true;
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return true;
    }

    /**
     * Rollback a transaction
     */
    public function rollBack(): bool
    {
        return true;
    }

    /**
     * Set an attribute
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        $this->attributes[$attribute] = $value;
        return true;
    }

    /**
     * Get an attribute
     */
    public function getAttribute(int $attribute): mixed
    {
        return $this->attributes[$attribute] ?? null;
    }
}

/**
 * TursoStatement - Emulates PDOStatement
 */
class TursoStatement
{
    private string $url;
    private string $token;
    private string $sql;
    private array $bindings = [];
    private array $rows = [];
    private int $rowIndex = 0;
    private int $rowCount = 0;

    public function __construct(string $url, string $token, string $sql)
    {
        $this->url = $url;
        $this->token = $token;
        $this->sql = $sql;
    }

    /**
     * Execute the statement
     */
    public function execute(?array $params = null): bool
    {
        try {
            $sql = $this->sql;

            // Replace named or positional parameters
            if ($params) {
                $this->bindings = $params;
                $params = array_values($params); // Convert to positional
                foreach ($params as $i => $value) {
                    $sql = str_replace('?', $this->quote($value), $sql, $count);
                    if ($count === 0) break;
                }
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->post("{$this->url}/execute", [
                'statements' => [['q' => $sql]],
            ]);

            if (!$response->successful()) {
                throw new \PDOException("Query failed: " . $response->body());
            }

            $result = $response->json()['results'][0] ?? [];
            $this->rows = $result['rows'] ?? [];
            $this->rowCount = $result['rows'] ? count($result['rows']) : ($result['affected_rows'] ?? 0);
            $this->rowIndex = 0;

            return true;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * Fetch the next row
     */
    public function fetch(int $fetchMode = PDO::FETCH_BOTH): mixed
    {
        if ($this->rowIndex >= count($this->rows)) {
            return false;
        }

        $row = $this->rows[$this->rowIndex++];

        return match ($fetchMode) {
            PDO::FETCH_ASSOC => (array)$row,
            PDO::FETCH_OBJ => $row,
            default => array_merge((array)$row, (array)$row),
        };
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(int $fetchMode = PDO::FETCH_BOTH): array
    {
        $results = [];
        while ($row = $this->fetch($fetchMode)) {
            $results[] = $row;
        }
        return $results;
    }

    /**
     * Fetch a single column
     */
    public function fetchColumn(int $columnKey = 0): mixed
    {
        $row = $this->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $values = array_values($row);
        return $values[$columnKey] ?? null;
    }

    /**
     * Get row count
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Quote a value for SQL
     */
    private function quote(mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value)) return (string)$value;
        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}
