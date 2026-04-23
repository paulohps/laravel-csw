---
name: csw-override-job
description: Override the vulnerability notification dispatch job used by Laravel Composer Security Watch (laravel-csw).
---

# CSW Override Notification Job

## When to use this skill
Replace `SendVulnerabilityNotificationsJob` when you need custom dispatch logic — e.g. batching, throttling, per-severity routing, or adding extra context before channels are called.

## Step 1 — Create the job

The constructor must accept `array $vulnerabilities` as its only argument:

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use LaravelCsw\Data\Vulnerability;

class MySecurityNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @param Vulnerability[] $vulnerabilities */
    public function __construct(
        private readonly array $vulnerabilities,
    ) {}

    public function handle(): void
    {
        // custom dispatch logic
    }
}
```

Do not add `SerializesModels` unless the job contains Eloquent models.

## Step 2 — Register in config

Override `notify.job.class` in `config/composer-security-watch.php`:

```php
'notify' => [
    'job' => [
        'class' => \App\Jobs\MySecurityNotificationJob::class,
        'queue' => env('CSW_JOB_QUEUE', 'default'),
    ],
],
```

The `queue` key is still used by the package to dispatch the job to the correct queue.

## Testing

```php
use Illuminate\Support\Facades\Bus;

Bus::fake();

// trigger the audit command or dispatch directly
\App\Jobs\MySecurityNotificationJob::dispatch([$vulnerability]);

Bus::assertDispatched(MySecurityNotificationJob::class);
```
