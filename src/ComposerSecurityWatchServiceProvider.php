<?php

namespace LaravelCsw;

use Illuminate\Console\Scheduling\Schedule;
use LaravelCsw\Commands\AuditCommand;
use LaravelCsw\Commands\InstallDatabaseChannelCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ComposerSecurityWatchServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('composer-security-watch')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                AuditCommand::class,
                InstallDatabaseChannelCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->publishMigrations();
        $this->registerSchedule();
    }

    private function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'composer-security-watch-migrations');
        }
    }

    private function registerSchedule(): void
    {
        if (! config('composer-security-watch.enabled', false)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $frequency = config('composer-security-watch.schedule.frequency', '0 9 * * *');
            $schedule->command('csw:audit --notify')->cron($frequency);
        });
    }
}
