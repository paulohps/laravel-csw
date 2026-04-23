<?php

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use LaravelCsw\Channels\EmailChannel;
use LaravelCsw\Data\Vulnerability;
use LaravelCsw\Mail\VulnerabilityReport;

class FakeVulnerabilityMailable extends Mailable
{
    public function __construct(public readonly array $vulnerabilities) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Custom Security Alert');
    }

    public function content(): Content
    {
        return new Content(view: 'composer-security-watch::emails.vulnerability-report');
    }
}

beforeEach(function (): void {
    Mail::fake();
});

it('sends an email to a single string recipient', function (): void {
    config(['composer-security-watch.notify.channels.email.to' => 'security@example.com']);

    (new EmailChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Mail::assertSent(VulnerabilityReport::class);
});

it('sends an email to multiple recipients', function (): void {
    config([
        'composer-security-watch.notify.channels.email.to' => [
            'admin@example.com',
            'security@example.com',
        ],
    ]);

    (new EmailChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Mail::assertSent(VulnerabilityReport::class);
});

it('does not send when the recipient is empty', function (): void {
    config(['composer-security-watch.notify.channels.email.to' => null]);

    (new EmailChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Mail::assertNothingSent();
});

it('does not send when the recipient is an empty string', function (): void {
    config(['composer-security-watch.notify.channels.email.to' => '']);

    (new EmailChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Mail::assertNothingSent();
});

it('uses a custom mailable class from config', function (): void {
    config([
        'composer-security-watch.notify.channels.email.to' => 'security@example.com',
        'composer-security-watch.notify.channels.email.mailable' => FakeVulnerabilityMailable::class,
    ]);

    (new EmailChannel())->send([
        new Vulnerability('vendor/pkg', '1.0.0', 'PKSA-001', 'XSS'),
    ]);

    Mail::assertSent(FakeVulnerabilityMailable::class);
    Mail::assertNotSent(VulnerabilityReport::class);
});
