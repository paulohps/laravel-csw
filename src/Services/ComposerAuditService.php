<?php

namespace LaravelCsw\Services;

use Illuminate\Support\Facades\Process;
use LaravelCsw\Data\Vulnerability;
use RuntimeException;

class ComposerAuditService
{
    public function audit(): array
    {
        $result = Process::run('composer audit --format=json --no-interaction');

        if ($result->exitCode() === 2) {
            throw new RuntimeException(
                'Failed to run composer audit: '.$result->errorOutput()
            );
        }

        $data = json_decode($result->output(), true);

        if (empty($data['advisories'])) {
            return [];
        }

        $installedVersions = $this->getInstalledVersions();
        $vulnerabilities = [];

        foreach ($data['advisories'] as $packageName => $advisories) {
            $version = $installedVersions[$packageName] ?? 'unknown';

            foreach ($advisories as $advisory) {
                $vulnerabilities[] = new Vulnerability(
                    packageName: $advisory['packageName'] ?? $packageName,
                    version: $version,
                    advisoryId: $advisory['advisoryId'] ?? '',
                    title: $advisory['title'] ?? '',
                    link: $advisory['link'] ?? null,
                    cve: $advisory['cve'] ?? null,
                );
            }
        }

        return $vulnerabilities;
    }

    public function update(array $packageNames, bool $withAllDependencies = false): bool
    {
        $command = array_merge(
            ['composer', 'update', '--no-interaction'],
            $packageNames
        );

        if ($withAllDependencies) {
            $command[] = '--with-all-dependencies';
        }

        $result = Process::run(implode(' ', $command));

        return $result->successful();
    }

    private function getInstalledVersions(): array
    {
        $lockFile = base_path('composer.lock');

        if (! file_exists($lockFile)) {
            return [];
        }

        $lock = json_decode(file_get_contents($lockFile), true);
        $versions = [];

        $packages = array_merge(
            $lock['packages'] ?? [],
            $lock['packages-dev'] ?? []
        );

        foreach ($packages as $package) {
            $versions[$package['name']] = $package['version'];
        }

        return $versions;
    }
}
