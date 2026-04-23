<?php

use Illuminate\Support\Facades\Log;
use LaravelCsw\Channels\LogChannel;
use LaravelCsw\Data\Vulnerability;

it('logs a warning for each vulnerability', function (): void {
    Log::spy();

    $channel = new LogChannel();
    $channel->send([
        new Vulnerability('vendor/pkg-a', '1.0.0', 'PKSA-0001', 'XSS', 'https://example.com', 'CVE-001'),
        new Vulnerability('vendor/pkg-b', '2.0.0', 'PKSA-0002', 'SQLi'),
    ]);

    Log::shouldHaveReceived('warning')
        ->twice()
        ->with(
            'Composer Security Watch: vulnerability found',
            \Mockery::type('array')
        );
});

it('includes all vulnerability fields in the log context', function (): void {
    Log::spy();

    $channel = new LogChannel();
    $channel->send([
        new Vulnerability(
            packageName: 'vendor/pkg',
            version: '1.2.3',
            advisoryId: 'PKSA-9999',
            title: 'Remote Code Execution',
            link: 'https://example.com/advisory',
            cve: 'CVE-2024-9999',
        ),
    ]);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Composer Security Watch: vulnerability found', [
            'package' => 'vendor/pkg',
            'version' => '1.2.3',
            'advisory_id' => 'PKSA-9999',
            'title' => 'Remote Code Execution',
            'link' => 'https://example.com/advisory',
            'cve' => 'CVE-2024-9999',
        ]);
});

it('does not log when vulnerabilities list is empty', function (): void {
    Log::spy();

    (new LogChannel())->send([]);

    Log::shouldNotHaveReceived('warning');
});
