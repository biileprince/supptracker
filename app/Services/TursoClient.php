<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TursoClient
{
    private string $url;
    private string $token;

    public function __construct(string $url, string $token)
    {
        $this->url = $url;
        $this->token = $token;
    }

    /**
     * Execute a SQL query against the Turso database.
     */
    public function query(string $sql, array $params = []): array
    {
        $statements = [
            [
                'q' => $sql,
                'params' => $params,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->post($this->url, [
                'statements' => $statements,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Query failed: " . $response->body());
            }

            return $response->json()['results'][0] ?? [];
        } catch (\Exception $e) {
            throw new \Exception("Turso query error: " . $e->getMessage());
        }
    }

    /**
     * Execute multiple SQL statements in a batch.
     */
    public function batch(array $queries): array
    {
        $statements = array_map(fn($q) => [
            'q' => $q,
        ], $queries);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->post($this->url, [
                'statements' => $statements,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Batch failed: " . $response->body());
            }

            return $response->json()['results'] ?? [];
        } catch (\Exception $e) {
            throw new \Exception("Turso batch error: " . $e->getMessage());
        }
    }

    /**
     * Get database information/stats.
     */
    public function info(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get($this->url);

            if (!$response->successful()) {
                throw new \Exception("Info request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Turso info error: " . $e->getMessage());
        }
    }
}
