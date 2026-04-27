<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TursoMigrateCommand extends Command
{
    protected $signature = 'turso:migrate {--fresh : Drop all tables and re-run migrations}';
    protected $protected = 'Migrate database schema to Turso';

    public function handle()
    {
        $url = config('database.connections.libsql.url');
        $token = config('database.connections.libsql.auth_token');

        if (!$url || !$token) {
            $this->error('Turso database URL and auth token must be configured in .env');
            return 1;
        }

        $this->info("Turso Database: {$url}");
        $this->info('Attempting to run migrations...');

        try {
            if ($this->option('fresh')) {
                $this->call('migrate:fresh', ['--force' => true]);
            } else {
                $this->call('migrate', ['--force' => true]);
            }

            $this->info('✓ Migrations completed successfully');
            $this->info('✓ Turso database is now ready');
            return 0;
        } catch (\Throwable $e) {
            $this->error('✗ Migration failed: ' . $e->getMessage());
            $this->line('Note: Turso integration in PHP 8.2 requires additional setup.');
            $this->line('For PHP 8.3+, ensure FFI extension is enabled.');
            return 1;
        }
    }
}
