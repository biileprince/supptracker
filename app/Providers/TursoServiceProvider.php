<?php

namespace App\Providers;

use App\Database\Turso\TursoPdoAdapter;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class TursoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register libsql connection resolver
        Connection::resolverFor('libsql', function ($connection, $database, $prefix, $config) {
            return $this->createLibsqlConnection($config, $prefix);
        });
    }

    protected function createLibsqlConnection($config, $prefix)
    {
        $url = $config['url'] ?? null;
        $token = $config['auth_token'] ?? null;

        if (!$url || !$token) {
            throw new \RuntimeException('Turso database URL and auth token are required');
        }

        // Try native SDK first (PHP 8.3+ with FFI)
        try {
            if (class_exists('Libsql\Database')) {
                $db = new \Libsql\Database($url, $token);
                $pdo = $db->getDbConnection();
                return new Connection($pdo, $database, $prefix, $config);
            }
        } catch (\Throwable $e) {
            // SDK not available, continue to HTTP adapter
        }

        // Use HTTP API adapter (PHP 8.2 compatible)
        $pdo = new TursoPdoAdapter($url, $token);
        return new Connection($pdo, $database, $prefix, $config);
