<?php

namespace App\Database\Turso;

use Illuminate\Support\Facades\Http;
use PDO;

/**
 * PDO-compatible wrapper for the Turso HTTP API.
 *
 * Enables Laravel to use Turso/libSQL over HTTP without requiring
 * the native libsql PHP extension or FFI.
 */
class TursoPdoAdapter
{
    /** @var array<int, mixed> */
    private array $attributes = [];

    public function __construct(
        private readonly string $url,
        private readonly string $token,
    ) {
        $this->attributes[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    }

    public function prepare(string $sql, array $options = []): TursoStatement
    {
        return new TursoStatement($this->url, $this->token, $sql);
    }

    public function exec(string $sql): int
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->post("{$this->url}/execute", [
            'statements' => [['q' => $sql]],
        ]);

        if (! $response->successful()) {
            throw new \PDOException('Query failed: '.$response->body());
        }

        return $response->json()['results'][0]['affected_rows'] ?? 0;
    }

    public function query(string $sql, ?int $fetchMode = null): TursoStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string
    {
        return "'".str_replace("'", "''", $string)."'";
    }

    public function lastInsertId(?string $name = null): string
    {
        $stmt = $this->query('SELECT last_insert_rowid() AS id');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (string) ($row['id'] ?? '0') : '0';
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollBack(): bool
    {
        return true;
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    public function getAttribute(int $attribute): mixed
    {
        return $this->attributes[$attribute] ?? null;
    }
}

/**
 * PDOStatement-compatible wrapper for Turso HTTP API responses.
 */
class TursoStatement
{
    /** @var array<int, mixed> */
    private array $bindings = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private int $rowIndex = 0;

    private int $rowCount = 0;

    public function __construct(
        private readonly string $url,
        private readonly string $token,
        private readonly string $sql,
    ) {}

    /**
     * @param  array<int|string, mixed>|null  $params
     */
    public function execute(?array $params = null): bool
    {
        $bindings = $params ?? $this->bindings;
        $sql = $this->sql;

        $args = [];
        if (! empty($bindings)) {
            $positionalValues = array_values($bindings);
            foreach ($positionalValues as $value) {
                $args[] = $this->formatArgValue($value);
            }
        }

        $statement = ['q' => $sql];
        if (! empty($args)) {
            $statement['params'] = $args;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->post("{$this->url}/execute", [
            'statements' => [$statement],
        ]);

        if (! $response->successful()) {
            throw new \PDOException('Query failed: '.$response->body());
        }

        $result = $response->json()['results'][0] ?? [];
        $this->rows = $result['rows'] ?? [];
        $this->rowCount = ! empty($result['rows']) ? count($result['rows']) : ($result['affected_rows'] ?? 0);
        $this->rowIndex = 0;

        return true;
    }

    public function fetch(int $fetchMode = PDO::FETCH_BOTH): mixed
    {
        if ($this->rowIndex >= count($this->rows)) {
            return false;
        }

        $row = $this->rows[$this->rowIndex++];

        return match ($fetchMode) {
            PDO::FETCH_ASSOC => (array) $row,
            PDO::FETCH_OBJ => (object) $row,
            default => array_merge((array) $row, array_values((array) $row)),
        };
    }

    public function fetchAll(int $fetchMode = PDO::FETCH_BOTH): array
    {
        $results = [];
        while ($row = $this->fetch($fetchMode)) {
            $results[] = $row;
        }

        return $results;
    }

    public function fetchColumn(int $columnKey = 0): mixed
    {
        $row = $this->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }

        $values = array_values($row);

        return $values[$columnKey] ?? null;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        if (is_int($param)) {
            $this->bindings[$param - 1] = $value;
        } else {
            $this->bindings[$param] = $value;
        }

        return true;
    }

    /**
     * Formats a PHP value for the Turso HTTP API params array.
     */
    private function formatArgValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }
}
