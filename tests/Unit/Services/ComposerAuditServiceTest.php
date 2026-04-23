<?php

use Illuminate\Support\Facades\Process;
use LaravelCsw\Data\Vulnerability;
use LaravelCsw\Services\ComposerAuditService;

beforeEach(function (): void {
    $this->service = new ComposerAuditService();
});

it('returns empty array when no vulnerabilities are found', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [],
                'metadata' => ['scanned' => 10, 'reported' => 0],
            ]),
            exitCode: 0,
        ),
    ]);

    expect($this->service->audit())->toBeEmpty();
});

it('returns vulnerabilities when composer audit finds issues', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-2024-0001',
                            'packageName' => 'vendor/package',
                            'title' => 'Remote Code Execution',
                            'link' => 'https://example.com/advisory',
                            'cve' => 'CVE-2024-1234',
                        ],
                    ],
                ],
                'metadata' => ['scanned' => 10, 'reported' => 1],
            ]),
            exitCode: 1,
        ),
    ]);

    $vulnerabilities = $this->service->audit();

    expect($vulnerabilities)->toHaveCount(1)
        ->and($vulnerabilities[0])->toBeInstanceOf(Vulnerability::class)
        ->and($vulnerabilities[0]->packageName)->toBe('vendor/package')
        ->and($vulnerabilities[0]->advisoryId)->toBe('PKSA-2024-0001')
        ->and($vulnerabilities[0]->title)->toBe('Remote Code Execution')
        ->and($vulnerabilities[0]->link)->toBe('https://example.com/advisory')
        ->and($vulnerabilities[0]->cve)->toBe('CVE-2024-1234');
});

it('returns version from composer.lock when available', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-2024-0001',
                            'packageName' => 'vendor/package',
                            'title' => 'Vuln',
                        ],
                    ],
                ],
                'metadata' => ['scanned' => 1, 'reported' => 1],
            ]),
            exitCode: 1,
        ),
    ]);

    $lockPath = base_path('composer.lock');
    file_put_contents($lockPath, json_encode([
        'packages' => [
            ['name' => 'vendor/package', 'version' => '1.2.3'],
        ],
        'packages-dev' => [],
    ]));

    $vulnerabilities = $this->service->audit();

    unlink($lockPath);

    expect($vulnerabilities[0]->version)->toBe('1.2.3');
});

it('reads version from packages-dev in composer.lock', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [
                    'vendor/dev-package' => [
                        [
                            'advisoryId' => 'PKSA-0001',
                            'packageName' => 'vendor/dev-package',
                            'title' => 'Dev Vuln',
                        ],
                    ],
                ],
                'metadata' => ['scanned' => 1, 'reported' => 1],
            ]),
            exitCode: 1,
        ),
    ]);

    $lockPath = base_path('composer.lock');
    file_put_contents($lockPath, json_encode([
        'packages' => [],
        'packages-dev' => [
            ['name' => 'vendor/dev-package', 'version' => '0.5.0'],
        ],
    ]));

    $vulnerabilities = $this->service->audit();

    unlink($lockPath);

    expect($vulnerabilities[0]->version)->toBe('0.5.0');
});

it('returns unknown version when composer.lock does not exist', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-0001',
                            'packageName' => 'vendor/package',
                            'title' => 'Vuln',
                        ],
                    ],
                ],
                'metadata' => ['scanned' => 1, 'reported' => 1],
            ]),
            exitCode: 1,
        ),
    ]);

    // Ensure composer.lock does not exist in the test environment
    $lockPath = base_path('composer.lock');
    $existed = file_exists($lockPath);
    if ($existed) {
        rename($lockPath, $lockPath.'.bak');
    }

    $vulnerabilities = $this->service->audit();

    if ($existed) {
        rename($lockPath.'.bak', $lockPath);
    }

    expect($vulnerabilities[0]->version)->toBe('unknown');
});

it('uses package name from advisory key when packageName field is missing', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: json_encode([
                'advisories' => [
                    'vendor/fallback-pkg' => [
                        [
                            'advisoryId' => 'PKSA-0002',
                            'title' => 'Missing field vuln',
                        ],
                    ],
                ],
                'metadata' => ['scanned' => 1, 'reported' => 1],
            ]),
            exitCode: 1,
        ),
    ]);

    $vulnerabilities = $this->service->audit();

    expect($vulnerabilities[0]->packageName)->toBe('vendor/fallback-pkg')
        ->and($vulnerabilities[0]->advisoryId)->toBe('PKSA-0002')
        ->and($vulnerabilities[0]->cve)->toBeNull()
        ->and($vulnerabilities[0]->link)->toBeNull();
});

it('throws a RuntimeException when composer audit fails with exit code 2', function (): void {
    Process::fake([
        'composer audit*' => Process::result(
            output: '',
            errorOutput: 'Composer is not installed',
            exitCode: 2,
        ),
    ]);

    expect(fn () => $this->service->audit())
        ->toThrow(RuntimeException::class, 'Failed to run composer audit');
});

it('returns true when update succeeds', function (): void {
    Process::fake([
        'composer update*' => Process::result(exitCode: 0),
    ]);

    expect($this->service->update(['vendor/package']))->toBeTrue();
});

it('returns false when update fails', function (): void {
    Process::fake([
        'composer update*' => Process::result(exitCode: 1),
    ]);

    expect($this->service->update(['vendor/package']))->toBeFalse();
});

it('passes --with-all-dependencies when withAllDependencies is true', function (): void {
    Process::fake([
        'composer update --no-interaction vendor/package --with-all-dependencies' => Process::result(exitCode: 0),
    ]);

    expect($this->service->update(['vendor/package'], withAllDependencies: true))->toBeTrue();

    Process::assertRan('composer update --no-interaction vendor/package --with-all-dependencies');
});

it('updates multiple packages at once', function (): void {
    Process::fake([
        'composer update*' => Process::result(exitCode: 0),
    ]);

    expect($this->service->update(['vendor/pkg-a', 'vendor/pkg-b']))->toBeTrue();

    Process::assertRan('composer update --no-interaction vendor/pkg-a vendor/pkg-b');
});
