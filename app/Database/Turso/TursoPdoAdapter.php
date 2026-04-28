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
class TursoPdoAdapter extends \PDO
{
    /** @var array<int, mixed> */
    private array $attributes = [];

    public function __construct(
        private readonly string $url,
        private readonly string $token,
    ) {
        $this->attributes[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return new TursoStatement($this->url, $this->token, $query);
    }

    public function exec(string $statement): int|false
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->post($this->url, [
            'statements' => [$statement],
        ]);

        if (! $response->successful()) {
            throw new \PDOException('Query failed: ' . $response->body());
        }

        $result = $response->json()[0]['results'] ?? [];
        return $result['affected_row_count'] ?? 0;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $stmt = clone $this->prepare($query);
        $stmt->execute();
        
        if ($fetchMode !== null) {
            $stmt->setFetchMode($fetchMode, ...$fetchModeArgs);
        }

        return $stmt;
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function lastInsertId(?string $name = null): string|false
    {
        $stmt = clone $this->prepare('SELECT last_insert_rowid() AS id');
        $stmt->execute();
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
class TursoStatement extends \PDOStatement
{
    /** @var array<int, mixed> */
    private array $bindings = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private int $rowIndex = 0;

    private int $rowCount = 0;
    
    private int $defaultFetchMode = PDO::FETCH_BOTH;

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

        // Send to root URL (V1 API) instead of /execute
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->post($this->url, [
            'statements' => [$statement],
        ]);

        if (! $response->successful()) {
            throw new \PDOException('Query failed: ' . $response->body());
        }

        // V1 API response format is an array of results
        $result = $response->json()[0]['results'] ?? [];
        
        if (isset($result['error'])) {
            throw new \PDOException('Query error: ' . $result['error']['message']);
        }
        
        $this->rows = [];
        if (!empty($result['columns']) && isset($result['rows'])) {
            foreach ($result['rows'] as $row) {
                // Map the array of values to associative array with column names
                $this->rows[] = array_combine($result['columns'], $row);
            }
        }

        $this->rowCount = isset($result['rows']) ? count($result['rows']) : ($result['affected_row_count'] ?? 0);
        $this->rowIndex = 0;

        return true;
    }

    public function fetch(int $mode = PDO::FETCH_BOTH, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->rowIndex >= count($this->rows)) {
            return false;
        }

        $row = $this->rows[$this->rowIndex++];
        $mode = $mode === PDO::FETCH_BOTH ? $this->defaultFetchMode : $mode;

        return match ($mode) {
            PDO::FETCH_ASSOC => (array) $row,
            PDO::FETCH_OBJ => (object) $row,
            default => array_merge((array) $row, array_values((array) $row)),
        };
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $results = [];
        $actualMode = $mode === PDO::FETCH_DEFAULT ? $this->defaultFetchMode : $mode;
        
        while ($row = $this->fetch($actualMode)) {
            $results[] = $row;
        }

        return $results;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            return false;
        }

        $values = array_values($row);

        return $values[$column] ?? false;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        if (is_int($param)) {
            $this->bindings[$param - 1] = $value;
        } else {
            $this->bindings[$param] = $value;
        }

        return true;
    }
    
    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        $this->defaultFetchMode = $mode;
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
