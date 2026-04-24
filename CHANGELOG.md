# Changelog

All notable changes to `laravel-csw` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.6] - 2026-04-23

### Changed

- Updated `README.md` to reflect the correct PHP (`^8.3`) and Laravel (`^11.0|^12.0`) requirements; config example now shows the full structure including `notify.job`, `class` keys per channel, and the `mailable` key for the email channel; added `CSW_JOB_QUEUE` to the environment variable reference; documented the `mailable` override for the email channel; clarified that the `class` key in config is the resolution mechanism for custom channels.
- Updated `CONTRIBUTING.md`: corrected PHP requirement to 8.3+; fixed constant name `BUILTIN_CHANNELS` → `BUILT_IN_CHANNELS`; clarified that the config entry must include a `class` key when adding a new built-in channel.
- Updated `CLAUDE.md`: corrected PHP and Laravel version references in the technology stack; added step for `BUILT_IN_CHANNELS` in the "Adding a new built-in channel" guide.

---

## [1.0.5] - 2026-04-23

### Changed

- Upgraded dev toolchain from Pest v3 to **Pest v4** (`pestphp/pest ^4.0`, `pest-plugin-arch ^4.0`, `pest-plugin-laravel ^4.0`).
- Added **Laravel 13** support: `illuminate/contracts ^13.0`, `orchestra/testbench ^11.0`, CI matrix column `13.*` with testbench `11.*`. Pest v4 / `pest-plugin-laravel ^4.1` is required for Laravel 13 compatibility.
- Removed `nunomaduro/collision ^7.10` constraint (Laravel 10 is no longer supported); constraint simplified to `^8.0`.

---

## [1.0.4] - 2026-04-23

### Changed

- Reverted Laravel 13 from the CI matrix and `composer.json`: `pestphp/pest-plugin-laravel ^3.x` does not yet support Laravel 13, making the matrix job fail at dependency resolution. Laravel 13 will be re-added once a compatible version of the Pest Laravel plugin is released.
- CI matrix now covers **PHP 8.3 / 8.4 / 8.5 × Laravel 11 / 12** (Laravel 11 restored, Laravel 13 removed).
- `illuminate/contracts` reverted to `^11.0||^12.0`; `orchestra/testbench` reverted to `^9.0||^10.0`.

---

## [1.0.3] - 2026-04-23

### Fixed

- Added three tests for `ComposerSecurityWatchServiceProviderTest` covering previously uncovered lines in `registerSchedule()`: the early-return branch when `enabled = false` (line 43) and the `callAfterResolving` callback body that reads the cron frequency and registers the command (lines 47–48). Code coverage is back to 100 %.

### Changed

- Dropped Laravel 10 support: removed `^10.0` from `illuminate/contracts` and `^8.22` from `orchestra/testbench` in `composer.json`.
- CI matrix: removed Laravel 10 column (and its testbench 8 include). Matrix now covers PHP 8.3–8.5 × Laravel 12–13.

---

## [1.0.2] - 2026-04-23

### Fixed

- `SendVulnerabilityNotificationsJob` now resolves built-in channels (`log`, `slack`, `discord`, `email`, `database`) by name when no `class` key is present in the channel config entry. Previously, only entries with an explicit `class` key were dispatched, causing built-in channels to be silently skipped when the config was overridden at runtime.

### Changed

- CI matrix: replaced Laravel 11 with Laravel 13, added PHP 8.4 and 8.5 to the test matrix (now covers PHP 8.3–8.5 × Laravel 10, 12, 13).
- `composer.json`: added `^13.0` to `illuminate/contracts` and `^11.0` to `orchestra/testbench` to formally declare Laravel 13 support.

---

## [1.0.1] - 2024-04-23

### Fixed

- CI matrix restricted to PHP 8.3+ to match the `^8.3` constraint declared in `composer.json`. PHP 8.2 jobs were failing on `composer update` due to the platform constraint mismatch.

---

## [1.0.0] - 2024-04-23

### Added

#### Core
- `Vulnerability` readonly DTO carrying advisory data (package name, version, advisory ID, title, CVE, link) between layers.
- `NotificationChannel` interface that all built-in and custom channels must implement, accepting an array of `Vulnerability` objects.
- `ComposerAuditService` — runs `composer audit --format=json` via the Laravel `Process` facade, parses advisories, resolves installed versions from `composer.lock`, and returns an array of `Vulnerability` DTOs. Also exposes an `update()` method for running `composer update` against vulnerable packages.
- `SendVulnerabilityNotificationsJob` — a `ShouldQueue` job that resolves enabled channels from config and dispatches `send()` on each one. The job class and target queue are configurable via `notify.job.class` and `notify.job.queue`.

#### Artisan Commands
- `csw:audit` — runs `composer audit`, displays a table of found vulnerabilities, and supports three flags:
  - `--notify` dispatches `SendVulnerabilityNotificationsJob` to configured channels.
  - `--update` runs `composer update` for each vulnerable package.
  - `--with-all` passes `--with-all-dependencies` to composer update (requires `--update`).
  - Returns exit code `0` (clean) or `1` (vulnerabilities found) — suitable for CI pipelines.
- `csw:install-database-channel` — publishes the database channel migration and prints next-step instructions.

#### Notification Channels
- **Log channel** (`LogChannel`) — writes a `warning` log entry per vulnerability using the `Log` facade. Enabled by default.
- **Slack channel** (`SlackChannel`) — posts a Block Kit message to a Slack Incoming Webhook URL. Each vulnerability is shown as a separate section with package, version, CVE, and advisory link.
- **Discord channel** (`DiscordChannel`) — posts embeds to a Discord webhook. Each vulnerability becomes a rich embed with color-coded fields.
- **Email channel** (`EmailChannel`) — sends a `VulnerabilityReport` Mailable to one or more recipients. Supports a single address string or an array of addresses. The mailable class is overridable via config.
- **Database channel** (`DatabaseChannel`) — persists each vulnerability to the `composer_security_vulnerabilities` table via the `VulnerabilityRecord` Eloquent model.

#### Email
- `VulnerabilityReport` Mailable with dynamic subject (`[Security] N Composer Vulnerabilities Found`).
- HTML email template (`resources/views/emails/vulnerability-report.blade.php`) with styled vulnerability cards.

#### Database
- Migration `create_composer_security_vulnerabilities_table` with columns: `id`, `package_name`, `version`, `advisory_id` (unique), `title`, `link`, `cve`, `found_at`, `timestamps`.
- `VulnerabilityRecord` Eloquent model with `$fillable` and `found_at` cast to `datetime`.

#### Service Provider
- `ComposerSecurityWatchServiceProvider` extending Spatie's `PackageServiceProvider`.
- Auto-registers config, views, and commands via `configurePackage()`.
- Publishes migrations under the `composer-security-watch-migrations` tag.
- Registers the scheduled `csw:audit --notify` command via `callAfterResolving(Schedule::class, ...)` only when `composer-security-watch.enabled` is `true`.

#### Configuration (`config/composer-security-watch.php`)
- `enabled` — enables/disables scheduled audit (`CSW_ENABLED`, default `true`).
- `schedule.frequency` — cron expression for the scheduler (`CSW_SCHEDULE_FREQUENCY`, default `0 9 * * *`).
- `notify.job.class` — overridable job class for dispatching notifications.
- `notify.job.queue` — target queue name (`CSW_JOB_QUEUE`, default `default`).
- Per-channel config blocks for `log`, `slack`, `discord`, `email`, and `database`, each with `enabled` flag and channel-specific options. Custom channels can be added with any key by providing `enabled` and `class`.

#### CI / Tooling
- GitHub Actions workflow (`.github/workflows/run-tests.yml`) running Pest across PHP 8.2 & 8.3 and Laravel 10, 11, and 12.
- Docker Compose environment (`.docker/php/Dockerfile`, `compose.yml`) for local development without a native PHP installation.
- Laravel Pint configured with the `laravel` preset (`pint.json`).
- Pest 3 test suite with 100 % coverage enforced (`--coverage --min=100`).

#### Tests
- `tests/Unit/Data/VulnerabilityTest.php` — DTO construction and `toArray()`.
- `tests/Unit/Services/ComposerAuditServiceTest.php` — successful audit, empty result, exit-code-2 failure, missing lock file, and `update()` success/failure paths.
- `tests/Unit/Channels/LogChannelTest.php` — single and multiple vulnerability logging.
- `tests/Unit/Channels/SlackChannelTest.php` — webhook payload with/without CVE/link, singular/plural header.
- `tests/Unit/Channels/DiscordChannelTest.php` — embed structure with/without optional fields.
- `tests/Unit/Channels/EmailChannelTest.php` — skips send when `to` is empty, sends to single address, sends to multiple addresses, uses custom mailable class.
- `tests/Unit/Channels/DatabaseChannelTest.php` — persists all vulnerability fields to the database.
- `tests/Unit/Mail/VulnerabilityReportTest.php` — envelope subject singular/plural, content view.
- `tests/Feature/Commands/AuditCommandTest.php` — clean audit, vulnerabilities found, `--notify` dispatch, `--update` success/failure, `--with-all` guard, process failure.
- `tests/Feature/Commands/InstallDatabaseChannelCommandTest.php` — publishes migration and prints instructions.
- `tests/Feature/Jobs/SendVulnerabilityNotificationsJobTest.php` — dispatches to enabled channels, skips disabled channels, skips invalid classes.

[1.0.0]: https://github.com/paulohps/laravel-csw/releases/tag/v1.0.0
