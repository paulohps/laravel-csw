---
name: csw-custom-channel
description: Create a custom notification channel for Laravel Composer Security Watch (laravel-csw).
---

# CSW Custom Notification Channel

## When to use this skill
Use this skill when adding a custom notification channel (e.g. PagerDuty, Teams, SMS) to receive composer vulnerability alerts from the `laravel-csw` package.

## Step 1 — Implement the interface

Create a class implementing `LaravelCsw\Contracts\NotificationChannel`:

```php
namespace App\Security;

use LaravelCsw\Contracts\NotificationChannel;
use LaravelCsw\Data\Vulnerability;

class PagerDutyChannel implements NotificationChannel
{
    public function send(array $vulnerabilities): void
    {
        // $vulnerabilities is Vulnerability[]
        // Each Vulnerability has: packageName, version, advisoryId, title, cve, link
    }
}
```

The `Vulnerability` DTO is readonly with these properties:
- `string $packageName`
- `string $version`
- `string $advisoryId`
- `string $title`
- `?string $cve`
- `?string $link`

## Step 2 — Register in config

Add an entry under `notify.channels` in `config/composer-security-watch.php`:

```php
'notify' => [
    'channels' => [
        // ... existing channels ...

        'pagerduty' => [
            'enabled' => env('CSW_NOTIFY_PAGERDUTY', false),
            'class' => \App\Security\PagerDutyChannel::class,
        ],
    ],
],
```

## Step 3 — Read channel-specific config (optional)

Read extra keys inside `send()`:

```php
$apiKey = config('composer-security-watch.notify.channels.pagerduty.api_key');
```

And add the key to the config entry:

```php
'pagerduty' => [
    'enabled' => env('CSW_NOTIFY_PAGERDUTY', false),
    'class'   => \App\Security\PagerDutyChannel::class,
    'api_key' => env('CSW_PAGERDUTY_API_KEY'),
],
```

## Testing

Use Laravel's built-in fakes — never mock Guzzle or curl directly:

```php
use Illuminate\Support\Facades\Http;

Http::fake(['https://events.pagerduty.com/*' => Http::response([], 202)]);

(new PagerDutyChannel())->send([$vulnerability]);

Http::assertSent(fn ($request) => str_contains($request->url(), 'pagerduty.com'));
```
