---
name: csw-override-email
description: Override the email mailable or Blade view used by Laravel Composer Security Watch (laravel-csw).
---

# CSW Override Email

Two ways to customise the vulnerability report email:

## Option A — Publish and edit the Blade view (template only)

Publish the view and edit the generated file:

```bash
php artisan vendor:publish --tag=composer-security-watch-views
```

This creates `resources/views/vendor/composer-security-watch/emails/vulnerability-report.blade.php`. Laravel automatically uses it over the package default. No config change needed.

Available variable in the view: `$vulnerabilities` — array of `LaravelCsw\Data\Vulnerability` objects, each with:
- `string $packageName`
- `string $version`
- `string $advisoryId`
- `string $title`
- `?string $cve`
- `?string $link`

## Option B — Replace the Mailable class

Create a Mailable with a constructor that accepts `array $vulnerabilities`:

```php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SecurityAlert extends Mailable
{
    public function __construct(
        public readonly array $vulnerabilities,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Security Alert: Composer Vulnerabilities');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.security-alert');
    }
}
```

Then point to it in `config/composer-security-watch.php`:

```php
'email' => [
    'enabled'  => env('CSW_NOTIFY_EMAIL', false),
    'class'    => \LaravelCsw\Channels\EmailChannel::class,
    'to'       => env('CSW_EMAIL_TO'),
    'mailable' => \App\Mail\SecurityAlert::class,
],
```

The constructor must accept `array $vulnerabilities` as its first and only argument.
