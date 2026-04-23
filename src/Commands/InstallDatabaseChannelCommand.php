<?php

namespace LaravelCsw\Commands;

use Illuminate\Console\Command;

class InstallDatabaseChannelCommand extends Command
{
    protected $signature = 'csw:install-database-channel';

    protected $description = 'Publish the database channel migration for Composer Security Watch';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'composer-security-watch-migrations',
            '--force' => false,
        ]);

        $this->newLine();
        $this->info('Migration published successfully.');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Run <comment>php artisan migrate</comment> to create the vulnerabilities table.');
        $this->line('  2. Enable the database channel in your config:');
        $this->line('     <comment>composer-security-watch.notify.channels.database.enabled = true</comment>');

        return self::SUCCESS;
    }
}
