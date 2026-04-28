<?php

namespace App\Providers;

use App\Database\Turso\TursoPdoAdapter;
use Illuminate\Database\SQLiteConnection;
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

    public function boot(): void {}

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createLibsqlConnection(array $config, string $name): SQLiteConnection
    {
        $url = $config['libsql_url'] ?? $config['url'] ?? null;
        $token = $config['password'] ?? null;

        if (empty($url) || empty($token)) {
            $missing = [];
            if (empty($url)) {
                $missing[] = 'TURSO_DATABASE_URL';
            }
            if (empty($token)) {
                $missing[] = 'TURSO_AUTH_TOKEN';
            }
            throw new \RuntimeException(
                'Turso configuration missing: '.implode(' and ', $missing)
                .'. Set these environment variables in Laravel Cloud.'
            );
        }

        // The HTTP adapter requires an http(s) URL, so convert libsql:// if present
        if (str_starts_with($url, 'libsql://')) {
            $url = 'https://' . substr($url, 9);
        } elseif (str_starts_with($url, 'libsql+wss://')) {
            $url = 'https://' . substr($url, 13);
        } elseif (str_starts_with($url, 'libsql+ws://')) {
            $url = 'http://' . substr($url, 12);
        }

        $database = $config['database'] ?? '';
        $prefix = $config['prefix'] ?? '';

        $pdo = new TursoPdoAdapter($url, $token);

        return new SQLiteConnection($pdo, $database, $prefix, $config);
    }
}
