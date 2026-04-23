## Laravel Composer Security Watch (laravel-csw)

Artisan package that runs `composer audit`, detects vulnerable dependencies, and
dispatches notifications through configurable channels.

### Key entry points

- **`csw:audit`** — CLI command; flags: `--notify`, `--update`, `--with-all`.
- **`SendVulnerabilityNotificationsJob`** — queued job that resolves and calls every enabled channel.
- **`NotificationChannel`** — interface every channel (built-in or custom) must implement.
- **`Vulnerability`** — readonly DTO passed to channels: `packageName`, `version`, `advisoryId`, `title`, `?cve`, `?link`.

### Config (`config/composer-security-watch.php`)

All behaviour is config-driven — no hardcoded values:

- `enabled` — master on/off switch; disables the scheduler when `false`.
- `schedule.frequency` — cron expression for the automated run.
- `notify.job.class` / `notify.job.queue` — job class and queue used for dispatch.
- `notify.channels.<name>.enabled` / `.class` — per-channel toggle and implementation class.
- `notify.channels.email.mailable` — Mailable class used by the email channel (overridable).

### Conventions

- Always use `Illuminate\Support\Facades\Process` — never `exec`, `shell_exec`, or `proc_open`.
- Use Laravel facades in tests: `Process::fake()`, `Http::fake()`, `Mail::fake()`, `Bus::fake()`, `Log::spy()`.
- Do not add `SerializesModels` to jobs that contain no Eloquent models.
- The database channel migration is opt-in: `php artisan csw:install-database-channel`.
- Do not auto-run migrations from the ServiceProvider.

### Adding or overriding a channel

Implement `LaravelCsw\Contracts\NotificationChannel` and register the class under `notify.channels` in the config:

```php
'my_channel' => [
    'enabled' => env('CSW_NOTIFY_MY_CHANNEL', false),
    'class'   => \App\Security\MyChannel::class,
],
```

### Overriding the email Mailable

Set `notify.channels.email.mailable` to any Mailable whose constructor accepts `array $vulnerabilities`:

```php
'email' => [
    'enabled'  => true,
    'class'    => \LaravelCsw\Channels\EmailChannel::class,
    'to'       => env('CSW_EMAIL_TO'),
    'mailable' => \App\Mail\SecurityAlert::class,
],
```

### Overriding the notification job

Set `notify.job.class` to any class whose constructor accepts `array $vulnerabilities`:

```php
'job' => [
    'class' => \App\Jobs\MySecurityNotificationJob::class,
    'queue' => env('CSW_JOB_QUEUE', 'default'),
],
```
