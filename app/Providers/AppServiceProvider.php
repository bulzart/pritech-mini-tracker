<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Issue;
use App\Models\Project;
use App\Policies\IssuePolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Explicit policy registration (in addition to Laravel's auto-discovery)
        // so the authorization rules are unambiguous and greppable.
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Issue::class, IssuePolicy::class);

        $this->ensureDemoDatabaseIsReady();
    }

    /**
     * Self-setup for a non-developer reviewer: on the first web request in demo
     * mode, create the SQLite file (if missing) and run migrations + seeders
     * when the schema is absent, so a fresh checkout "just works" on
     * `php artisan serve` with no manual migrate/seed step.
     *
     * Guards: only in demo mode (never auto-migrates a real deployment); only
     * for web requests, never inside an artisan command (so it cannot recurse
     * into the migrate command it triggers); only for a file-based SQLite
     * connection.
     */
    private function ensureDemoDatabaseIsReady(): void
    {
        if ($this->app->runningInConsole() || ! config('app.demo_mode')) {
            return;
        }

        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === ':memory:') {
            return;
        }

        try {
            if (! file_exists($database)) {
                touch($database);
            }

            if (! Schema::hasTable('users')) {
                Artisan::call('migrate', ['--force' => true, '--seed' => true]);
            }
        } catch (Throwable $exception) {
            // Surface the failure via the log rather than serving a
            // half-initialised app; the next DB access then raises a clear
            // error instead of a silent blank page.
            report($exception);
        }
    }
}
