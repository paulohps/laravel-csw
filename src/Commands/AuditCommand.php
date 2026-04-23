<?php

namespace LaravelCsw\Commands;

use Illuminate\Console\Command;
use LaravelCsw\Data\Vulnerability;
use LaravelCsw\Services\ComposerAuditService;
use RuntimeException;

class AuditCommand extends Command
{
    protected $signature = 'csw:audit
                            {--notify : Dispatch a job to send vulnerability notifications to configured channels}
                            {--update : Run composer update for packages with vulnerabilities}
                            {--with-all : Pass --with-all-dependencies to composer update (requires --update)}';

    protected $description = 'Run composer audit and check for security vulnerabilities';

    public function handle(ComposerAuditService $auditService): int
    {
        if ($this->option('with-all') && ! $this->option('update')) {
            $this->error('The --with-all option requires --update to be passed.');

            return self::FAILURE;
        }

        $this->info('Running composer audit...');

        try {
            $vulnerabilities = $auditService->audit();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($vulnerabilities)) {
            $this->info('No vulnerabilities found.');

            return self::SUCCESS;
        }

        $count = count($vulnerabilities);
        $this->warn("Found {$count} ".($count === 1 ? 'vulnerability' : 'vulnerabilities').'.');
        $this->displayVulnerabilities($vulnerabilities);

        if ($this->option('notify')) {
            $jobClass = config('composer-security-watch.notify.job.class');
            $queue = config('composer-security-watch.notify.job.queue', 'default');
            dispatch(new $jobClass($vulnerabilities))->onQueue($queue);
            $this->info('Notification job dispatched.');
        }

        if ($this->option('update')) {
            $this->updateVulnerablePackages($vulnerabilities, $auditService);
        }

        return self::FAILURE;
    }

    /** @param  Vulnerability[]  $vulnerabilities */
    private function displayVulnerabilities(array $vulnerabilities): void
    {
        $this->table(
            ['Package', 'Version', 'Advisory ID', 'Title', 'CVE'],
            array_map(fn (Vulnerability $v) => [
                $v->packageName,
                $v->version,
                $v->advisoryId,
                $v->title,
                $v->cve ?? '-',
            ], $vulnerabilities)
        );
    }

    /** @param  Vulnerability[]  $vulnerabilities */
    private function updateVulnerablePackages(array $vulnerabilities, ComposerAuditService $auditService): void
    {
        $packages = array_unique(array_map(fn (Vulnerability $v) => $v->packageName, $vulnerabilities));

        $this->info('Updating '.implode(', ', $packages).'...');

        $success = $auditService->update($packages, (bool) $this->option('with-all'));

        if ($success) {
            $this->info('Packages updated successfully.');
        } else {
            $this->error('Failed to update some packages. Check composer output above.');
        }
    }
}
