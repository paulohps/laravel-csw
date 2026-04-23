<?php

namespace LaravelCsw\Channels;

use Illuminate\Support\Facades\Http;
use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Data\Vulnerability;

class SlackChannel implements NotificationChannel
{
    public function send(array $vulnerabilities): void
    {
        $webhookUrl = config('composer-security-watch.notify.channels.slack.webhook_url');

        Http::post($webhookUrl, [
            'blocks' => $this->buildBlocks($vulnerabilities),
        ]);
    }

    private function buildBlocks(array $vulnerabilities): array
    {
        $count = count($vulnerabilities);
        $header = $count === 1
            ? '1 Composer Security Vulnerability Found'
            : "{$count} Composer Security Vulnerabilities Found";

        $blocks = [
            [
                'type' => 'header',
                'text' => ['type' => 'plain_text', 'text' => $header],
            ],
            ['type' => 'divider'],
        ];

        foreach ($vulnerabilities as $vulnerability) {
            $text = "*{$vulnerability->packageName}* (`{$vulnerability->version}`)\n"
                ."{$vulnerability->title}";

            if ($vulnerability->cve) {
                $text .= "\n*CVE:* {$vulnerability->cve}";
            }

            if ($vulnerability->link) {
                $text .= "\n<{$vulnerability->link}|View Advisory>";
            }

            $blocks[] = [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => $text],
            ];
        }

        return $blocks;
    }
}
