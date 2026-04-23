<?php

namespace LaravelCsw\Channels;

use Illuminate\Support\Facades\Log;
use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Data\Vulnerability;

class LogChannel implements NotificationChannel
{
    public function send(array $vulnerabilities): void
    {
        foreach ($vulnerabilities as $vulnerability) {
            Log::warning('Composer Security Watch: vulnerability found', [
                'package' => $vulnerability->packageName,
                'version' => $vulnerability->version,
                'advisory_id' => $vulnerability->advisoryId,
                'title' => $vulnerability->title,
                'link' => $vulnerability->link,
                'cve' => $vulnerability->cve,
            ]);
        }
    }
}
