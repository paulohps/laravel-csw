<?php

use Illuminate\Support\Facades\Http;
use LaravelCsw\Channels\SlackChannel;
use LaravelCsw\Data\Vulnerability;

beforeEach(function (): void {
    config(['composer-security-watch.notify.channels.slack.webhook_url' => 'https://hooks.slack.com/test']);
    Http::fake();
});

it('sends a POST request to the slack webhook url', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.slack.com/test');
});

it('includes blocks in the slack payload', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(function ($request): bool {
        $data = $request->data();

        return isset($data['blocks']) && count($data['blocks']) > 0;
    });
});

it('uses singular text for one vulnerability', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Http::assertSent(function ($request): bool {
        $header = $request->data()['blocks'][0]['text']['text'] ?? '';

        return str_contains($header, '1 Composer Security Vulnerability Found');
    });
});

it('uses plural text for multiple vulnerabilities', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg-a', '1.0.0', 'PKSA-001', 'XSS'),
        new Vulnerability('vendor/pkg-b', '2.0.0', 'PKSA-002', 'SQLi'),
    ]);

    Http::assertSent(function ($request): bool {
        $header = $request->data()['blocks'][0]['text']['text'] ?? '';

        return str_contains($header, '2 Composer Security Vulnerabilities Found');
    });
});

it('includes cve in the block text when present', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS', null, 'CVE-2024-1234'),
    ]);

    Http::assertSent(function ($request): bool {
        $sectionText = $request->data()['blocks'][2]['text']['text'] ?? '';

        return str_contains($sectionText, 'CVE-2024-1234');
    });
});

it('includes advisory link in the block text when present', function (): void {
    (new SlackChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS', 'https://example.com'),
    ]);

    Http::assertSent(function ($request): bool {
        $sectionText = $request->data()['blocks'][2]['text']['text'] ?? '';

        return str_contains($sectionText, 'https://example.com');
    });
});
