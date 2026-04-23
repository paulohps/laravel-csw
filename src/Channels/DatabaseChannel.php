<?php

namespace LaravelCsw\Channels;

use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Data\Vulnerability;
use LaravelCsw\Models\VulnerabilityRecord;

class DatabaseChannel implements NotificationChannel
{
    public function send(array $vulnerabilities): void
    {
        foreach ($vulnerabilities as $vulnerability) {
            VulnerabilityRecord::create([
                'package_name' => $vulnerability->packageName,
                'version' => $vulnerability->version,
                'advisory_id' => $vulnerability->advisoryId,
                'title' => $vulnerability->title,
                'link' => $vulnerability->link,
                'cve' => $vulnerability->cve,
                'found_at' => now(),
            ]);
        }
    }
}
