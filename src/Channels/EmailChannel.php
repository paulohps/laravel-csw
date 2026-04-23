<?php

namespace LaravelCsw\Channels;

use Illuminate\Support\Facades\Mail;
use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Mail\VulnerabilityReport;

class EmailChannel implements NotificationChannel
{
    public function send(array $vulnerabilities): void
    {
        $to = config('composer-security-watch.notify.channels.email.to');

        if (empty($to)) {
            return;
        }

        $recipients = is_array($to) ? $to : [$to];

        $mailableClass = config(
            'composer-security-watch.notify.channels.email.mailable',
            VulnerabilityReport::class
        );

        Mail::to($recipients)->send(new $mailableClass($vulnerabilities));
    }
}
