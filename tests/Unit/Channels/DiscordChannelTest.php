<?php

use Illuminate\Support\Facades\Http;
use LaravelCsw\Channels\DiscordChannel;
use LaravelCsw\Data\Vulnerability;

beforeEach(function (): void {
    config(['composer-security-watch.notify.channels.discord.webhook_url' => 'https://discord.com/api/webhooks/test']);
    Http::fake();
});

it('sends a POST request to the discord webhook url', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(fn ($r) => $r->url() === 'https://discord.com/api/webhooks/test');
});

it('uses singular content for one vulnerability', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(function ($request): bool {
        return str_contains($request->data()['content'] ?? '', '1 Composer Security Vulnerability Found');
    });
});

it('uses plural content for multiple vulnerabilities', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg-a', '1.0.0', 'PKSA-001', 'XSS'),
        new Vulnerability('vendor/pkg-b', '2.0.0', 'PKSA-002', 'SQLi'),
    ]);

    Http::assertSent(function ($request): bool {
        return str_contains($request->data()['content'] ?? '', '2 Composer Security Vulnerabilities Found');
    });
});

it('sends embeds for each vulnerability', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg-a', '1.0.0', 'PKSA-001', 'XSS'),
        new Vulnerability('vendor/pkg-b', '2.0.0', 'PKSA-002', 'SQLi'),
    ]);

    Http::assertSent(function ($request): bool {
        $embeds = $request->data()['embeds'] ?? [];

        return count($embeds) === 2;
    });
});

it('includes cve field in embed when present', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS', null, 'CVE-2024-1234'),
    ]);

    Http::assertSent(function ($request): bool {
        $fields = $request->data()['embeds'][0]['fields'] ?? [];
        $fieldNames = array_column($fields, 'name');

        return in_array('CVE', $fieldNames);
    });
});

it('includes advisory link field in embed when present', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS', 'https://example.com'),
    ]);

    Http::assertSent(function ($request): bool {
        $fields = $request->data()['embeds'][0]['fields'] ?? [];
        $fieldNames = array_column($fields, 'name');

        return in_array('Advisory', $fieldNames);
    });
});

it('omits cve field when not present', function (): void {
    (new DiscordChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(function ($request): bool {
        $fields = $request->data()['embeds'][0]['fields'] ?? [];
        $fieldNames = array_column($fields, 'name');

        return ! in_array('CVE', $fieldNames) && ! in_array('Advisory', $fieldNames);
    });
});
