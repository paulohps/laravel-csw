<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use LaravelCsw\Jobs\SendVulnerabilityNotificationsJob;

function noVulnerabilitiesResult(): array
{
    return [
        'advisories' => [],
        'metadata' => ['scanned' => 10, 'reported' => 0],
    ];
}

function oneVulnerabilityResult(): array
{
    return [
        'advisories' => [
            'vendor/pkg' => [
                [
                    'advisoryId' => 'PKSA-001',
                    'packageName' => 'vendor/pkg',
                    'title' => 'XSS Vulnerability',
                    'link' => 'https://example.com',
                    'cve' => 'CVE-2024-1',
                ],
            ],
        ],
        'metadata' => ['scanned' => 10, 'reported' => 1],
    ];
}

it('returns success when no vulnerabilities are found', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(noVulnerabilitiesResult()),
            exitCode: 0,
        ),
    ]);

    $this->artisan('csw:audit')
        ->expectsOutput('Running composer audit...')
        ->expectsOutput('No vulnerabilities found.')
        ->assertExitCode(0);
});

it('returns failure and shows vulnerabilities when found', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(oneVulnerabilityResult()),
            exitCode: 1,
        ),
    ]);

    $this->artisan('csw:audit')
        ->assertExitCode(1);
});

it('dispatches the notification job when --notify is passed', function (): void {
    Bus::fake();
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(oneVulnerabilityResult()),
            exitCode: 1,
        ),
    ]);

    $this->artisan('csw:audit --notify')
        ->expectsOutput('Notification job dispatched.')
        ->assertExitCode(1);

    Bus::assertDispatched(SendVulnerabilityNotificationsJob::class);
});

it('does not dispatch the notification job when no vulnerabilities', function (): void {
    Bus::fake();
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(noVulnerabilitiesResult()),
            exitCode: 0,
        ),
    ]);

    $this->artisan('csw:audit --notify')->assertExitCode(0);

    Bus::assertNotDispatched(SendVulnerabilityNotificationsJob::class);
});

it('runs update when --update is passed and vulnerabilities are found', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(oneVulnerabilityResult()),
            exitCode: 1,
        ),
        'composer update*' => Process::result(exitCode: 0),
    ]);

    $this->artisan('csw:audit --update')
        ->expectsOutput('Packages updated successfully.')
        ->assertExitCode(1);
});

it('shows an error when update fails', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(oneVulnerabilityResult()),
            exitCode: 1,
        ),
        'composer update*' => Process::result(exitCode: 1),
    ]);

    $this->artisan('csw:audit --update')
        ->assertExitCode(1);
});

it('passes --with-all-dependencies to composer update when --with-all is used with --update', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode(oneVulnerabilityResult()),
            exitCode: 1,
        ),
        'composer update --no-interaction vendor/pkg --with-all-dependencies' => Process::result(exitCode: 0),
    ]);

    $this->artisan('csw:audit --update --with-all')
        ->expectsOutput('Packages updated successfully.')
        ->assertExitCode(1);

    Process::assertRan('composer update --no-interaction vendor/pkg --with-all-dependencies');
});

it('returns failure when --with-all is passed without --update', function (): void {
    $this->artisan('csw:audit --with-all')
        ->expectsOutput('The --with-all option requires --update to be passed.')
        ->assertExitCode(1);
});

it('returns failure when composer audit throws a runtime exception', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: '',
            errorOutput: 'command not found',
            exitCode: 2,
        ),
    ]);

    $this->artisan('csw:audit')->assertExitCode(1);
});
