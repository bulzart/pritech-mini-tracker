<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * One-command first-time setup for a non-developer reviewer:
 *
 *   php artisan app:install
 *
 * creates the environment file + app key, the SQLite database, and the demo
 * data — after which `php artisan serve` runs a fully working, seeded app with
 * the login form prefilled. Safe to re-run: it only seeds when the database is
 * empty (pass --fresh to drop everything and reseed).
 */
final class InstallCommand extends Command
{
    protected $signature = 'app:install {--fresh : Drop all tables and reseed from scratch}';

    protected $description = 'Set up the environment, database, and demo data for a fresh checkout';

    public function handle(): int
    {
        $this->ensureEnvFile();
        $this->ensureAppKey();
        $this->ensureDatabaseFile();
        $this->migrateAndSeed();

        $this->newLine();
        $this->info('Setup complete. Start the app with:');
        $this->line('  php artisan serve');
        $this->line('Then open the printed URL and sign in — in demo mode the credentials are prefilled.');

        return self::SUCCESS;
    }

    private function ensureEnvFile(): void
    {
        $env = base_path('.env');

        if (! file_exists($env)) {
            copy(base_path('.env.example'), $env);
            $this->info('Created .env from .env.example.');
        }

        // This is a demo/assignment app: enable demo mode so the login form is
        // prefilled for the reviewer. Production deployments set DEMO_MODE=false
        // (documented in .env.example).
        $contents = (string) file_get_contents($env);

        $contents = preg_match('/^DEMO_MODE=/m', $contents) === 1
            ? (string) preg_replace('/^DEMO_MODE=.*/m', 'DEMO_MODE=true', $contents)
            : rtrim($contents, "\n")."\nDEMO_MODE=true\n";

        file_put_contents($env, $contents);
    }

    private function ensureAppKey(): void
    {
        if (blank(config('app.key')) && blank(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true], $this->output);
        }
    }

    private function ensureDatabaseFile(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (is_string($database) && $database !== ':memory:' && ! file_exists($database)) {
            touch($database);
            $this->info('Created the SQLite database file.');
        }
    }

    private function migrateAndSeed(): void
    {
        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true], $this->output);

            return;
        }

        Artisan::call('migrate', ['--force' => true], $this->output);

        // Seed only when there is no data yet, so re-running app:install does not
        // create duplicate demo records (the demo emails are unique).
        if (Schema::hasTable('users') && User::query()->doesntExist()) {
            Artisan::call('db:seed', ['--force' => true], $this->output);
        }
    }
}
