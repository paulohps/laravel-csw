# Laravel CSW — Claude Guidelines

## Package overview

**Laravel Composer Security Watch** is a Laravel package built on the Spatie package skeleton pattern. It provides an Artisan command (`csw:audit`) that wraps `composer audit`, detects vulnerable dependencies, and notifies configured channels.

## Technology stack

- **PHP 8.3+** — use readonly classes, named arguments, match expressions, fibers where appropriate
- **Laravel 11/12** — use Laravel facades (Process, Http, Mail, Log, Bus) instead of low-level PHP equivalents
- **Spatie Laravel Package Tools** — ServiceProvider extends `PackageServiceProvider`, configured via `configurePackage()`
- **Pest 3** — all tests use Pest syntax with arrow functions; zero PHPUnit-style test classes
- **Laravel Pint (laravel preset)** — enforced by CI

## Core design principles

- **No abstractions ahead of need.** Don't create managers, registries, or base classes unless required by two or more concrete cases today.
- **Leverage Laravel.** Use `Process::fake()`, `Http::fake()`, `Mail::fake()`, `Log::spy()`, `Bus::fake()` in tests — never mock Guzzle, Symfony Process, or Swift Mailer directly.
- **100% test coverage enforced.** CI runs `pest --coverage --min=100`. Every branch, every null path, every error path must have a test.
- **Config-driven behavior.** Channel enabling/disabling, schedule frequency, and webhook URLs come from `config/composer-security-watch.php`. No hardcoded values.

## Key files

| File | Purpose |
|---|---|
| `src/ComposerSecurityWatchServiceProvider.php` | Registers config, views, commands, migrations, schedule |
| `src/Services/ComposerAuditService.php` | Runs `composer audit` via `Process` facade, parses JSON output |
| `src/Jobs/SendVulnerabilityNotificationsJob.php` | Resolves enabled channels from config and dispatches notifications |
| `src/Contracts/NotificationChannel.php` | Interface all channels (built-in and custom) must implement |
| `src/Data/Vulnerability.php` | Readonly DTO carrying advisory data between layers |
| `config/composer-security-watch.php` | Single source of truth for all package configuration |

## Commands

| Command | Purpose |
|---|---|
| `csw:audit` | Run composer audit; supports `--notify`, `--update`, `--with-all` |
| `csw:install-database-channel` | Publish the database channel migration |

## Adding a new built-in channel

1. Create `src/Channels/XChannel.php` implementing `NotificationChannel`.
2. Add a config entry in `config/composer-security-watch.php` under `notify.channels` with a `class` key pointing to the new channel.
3. Add the channel to `SendVulnerabilityNotificationsJob::BUILT_IN_CHANNELS` as a fallback for configs that omit the `class` key.
4. Write tests in `tests/Unit/Channels/XChannelTest.php` covering all branches.
5. Update `README.md`.

## Testing conventions

```php
// Prefer these patterns:
Process::fake(['composer audit*' => Process::result(output: '...', exitCode: 1)]);
Http::fake(['https://hooks.slack.com/*' => Http::response([], 200)]);
Mail::fake();
Log::spy();
Bus::fake();

// Test both happy path AND every conditional branch
it('does nothing when list is empty', fn () => ...);
it('does X when condition A', fn () => ...);
it('does Y when condition B', fn () => ...);
```

## What NOT to do

- Do not use `shell_exec`, `exec`, `proc_open` — always use `Illuminate\Support\Facades\Process`.
- Do not add `SerializesModels` to jobs that don't contain Eloquent models.
- Do not create Facade classes — this package exposes only Artisan commands and a Job.
- Do not auto-run the database migration from the ServiceProvider — it must be opt-in via `csw:install-database-channel`.
- Do not add comments explaining what code does — only add comments for non-obvious *why*.

## Laravel scheduler registration

The schedule is registered in `packageBooted()` via `callAfterResolving(Schedule::class, ...)`. This pattern is correct for Spatie packages. The command is only registered when `composer-security-watch.enabled` is `true`.

## Queue behaviour

`SendVulnerabilityNotificationsJob` implements `ShouldQueue`. With `QUEUE_CONNECTION=sync` (default in tests and typical local dev), it runs synchronously. In production with a real queue driver, it runs async. This is intentional — no conditional dispatch needed.

The job class and queue are resolved from config at dispatch time (`notify.job.class` and `notify.job.queue`). Override `notify.job.class` to replace the job entirely; set `CSW_JOB_QUEUE` (or `notify.job.queue` directly) to route it to a specific queue.
