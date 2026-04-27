<?php

namespace App\Providers;

use App\Database\Turso\TursoPdoAdapter;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class TursoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('libsql', function ($config, $name) {
                return $this->createLibsqlConnection($config, $name);
            });
        });
    }

    public function boot(): void
    {
        //
    }

    protected function createLibsqlConnection($config, $name)
    {
        $url = $config['libsql_url'] ?? $config['url'] ?? null;
        $token = $config['password'] ?? $config['auth_token'] ?? null;

        if (empty($url) || empty($token)) {
            $missing = [];
            if (empty($url)) $missing[] = 'URL';
            if (empty($token)) $missing[] = 'Token';
            throw new \RuntimeException('Turso configuration missing: ' . implode(' and ', $missing) . '. Check environment variables on Laravel Cloud (DB_URL / TURSO_DATABASE_URL and TURSO_AUTH_TOKEN).');
        }

        // Try native SDK first (PHP 8.3+ with FFI)
        $database = $config['database'] ?? '';
        $prefix = $config['prefix'] ?? '';

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
    }
}
