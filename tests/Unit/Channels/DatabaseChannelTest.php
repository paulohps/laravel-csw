<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelCsw\Channels\DatabaseChannel;
use LaravelCsw\Data\Vulnerability;
use LaravelCsw\Models\VulnerabilityRecord;

uses(RefreshDatabase::class);

it('creates a record for each vulnerability', function (): void {
    (new DatabaseChannel())->send([
        new Vulnerability('vendor/pkg-a', '1.0.0', 'PKSA-001', 'XSS', 'https://example.com', 'CVE-001'),
        new Vulnerability('vendor/pkg-b', '2.0.0', 'PKSA-002', 'SQLi'),
    ]);

    expect(VulnerabilityRecord::count())->toBe(2);
});

it('stores all fields correctly in the database', function (): void {
    (new DatabaseChannel())->send([
        new Vulnerability(
            packageName: 'vendor/pkg',
            version: '1.5.0',
            advisoryId: 'PKSA-9999',
            title: 'Remote Code Execution',
            link: 'https://example.com/advisory',
            cve: 'CVE-2024-9999',
        ),
    ]);

    $record = VulnerabilityRecord::first();

    expect($record->package_name)->toBe('vendor/pkg')
        ->and($record->version)->toBe('1.5.0')
        ->and($record->advisory_id)->toBe('PKSA-9999')
        ->and($record->title)->toBe('Remote Code Execution')
        ->and($record->link)->toBe('https://example.com/advisory')
        ->and($record->cve)->toBe('CVE-2024-9999')
        ->and($record->found_at)->not->toBeNull();
});

it('stores null fields correctly', function (): void {
    (new DatabaseChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    $record = VulnerabilityRecord::first();

    expect($record->link)->toBeNull()
        ->and($record->cve)->toBeNull();
});

it('does not create records when vulnerabilities list is empty', function (): void {
    (new DatabaseChannel())->send([]);

    expect(VulnerabilityRecord::count())->toBe(0);
});
