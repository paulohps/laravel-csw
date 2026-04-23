<?php

namespace LaravelCsw\Channels;

use Illuminate\Support\Facades\Http;
use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Data\Vulnerability;

class DiscordChannel implements NotificationChannel
{
    private const COLOR_RED = 15158332;

    public function send(array $vulnerabilities): void
    {
        $webhookUrl = config('composer-security-watch.notify.channels.discord.webhook_url');

        $count = count($vulnerabilities);
        $content = $count === 1
            ? '**1 Composer Security Vulnerability Found**'
            : "**{$count} Composer Security Vulnerabilities Found**";

        Http::post($webhookUrl, [
            'content' => $content,
            'embeds' => $this->buildEmbeds($vulnerabilities),
        ]);
    }

    private function buildEmbeds(array $vulnerabilities): array
    {
        return array_map(function (Vulnerability $vulnerability): array {
            $fields = [
                ['name' => 'Package', 'value' => $vulnerability->packageName, 'inline' => true],
                ['name' => 'Version', 'value' => $vulnerability->version, 'inline' => true],
                ['name' => 'Advisory ID', 'value' => $vulnerability->advisoryId, 'inline' => true],
            ];

            if ($vulnerability->cve) {
                $fields[] = ['name' => 'CVE', 'value' => $vulnerability->cve, 'inline' => true];
            }

            if ($vulnerability->link) {
                $fields[] = ['name' => 'Advisory', 'value' => $vulnerability->link, 'inline' => false];
            }

            return [
                'title' => $vulnerability->title,
                'color' => self::COLOR_RED,
                'fields' => $fields,
            ];
        }, $vulnerabilities);
    }
}
