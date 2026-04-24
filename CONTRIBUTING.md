# Contributing to Laravel CSW

Thank you for considering contributing to Laravel Composer Security Watch! This document outlines the process and standards for contributing.

---

## Code of Conduct

Please be respectful, constructive, and inclusive in all interactions. We follow the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct.

---

## How to Contribute

### Reporting Bugs

Before opening an issue, please:

1. Search existing issues to avoid duplicates.
2. Confirm the bug is reproducible with the latest version.
3. Include:
   - PHP version (`php --version`)
   - Laravel version (`php artisan --version`)
   - Package version
   - Minimal reproduction steps
   - Expected vs. actual behavior

### Suggesting Features

Open a GitHub issue with the `[Feature Request]` prefix. Describe the use case and why the feature is valuable. We discuss proposals before implementation.

### Submitting Pull Requests

1. Fork the repository and create a branch from `main`.
2. Branch naming convention: `feat/short-description`, `fix/short-description`, `chore/description`.
3. Write or update tests for all changes (100% coverage required).
4. Ensure all tests pass and code style is clean before opening a PR.
5. Keep commits focused and atomic — one logical change per commit.
6. Write a clear PR description explaining *what* and *why*.

---

## Development Setup

### Option A — Docker (recommended, no local PHP needed)

```bash
# Clone and build
git clone https://github.com/your-vendor/laravel-csw.git
cd laravel-csw
docker compose build

# Install dependencies
docker compose run --rm app composer install

# Run tests
docker compose run --rm app vendor/bin/pest --coverage

# Apply code style
docker compose run --rm app vendor/bin/pint
```

### Option B — Local PHP

Requirements: PHP 8.3+, Composer 2.x.

```bash
git clone https://github.com/your-vendor/laravel-csw.git
cd laravel-csw
composer install
```

---

## Running Tests

```bash
# All tests
vendor/bin/pest

# With coverage (enforces 100%)
vendor/bin/pest --coverage --min=100

# Specific test file
vendor/bin/pest tests/Unit/Services/ComposerAuditServiceTest.php

# Specific test by description
vendor/bin/pest --filter "returns vulnerabilities"
```

### Coverage Requirements

**Every line, branch, and function must be covered.** CI enforces `--min=100`. There are no exceptions — if you add code, add tests.

Strategies:
- Test every conditional branch (both `true` and `false` paths).
- Test edge cases: empty arrays, null values, missing config, failed processes.
- Use `Process::fake()`, `Http::fake()`, `Mail::fake()`, and `Log::spy()` to avoid real side effects.

---

## Code Style

This project uses [Laravel Pint](https://github.com/laravel/pint) with the `laravel` preset.

```bash
# Check style
vendor/bin/pint --test

# Fix style
vendor/bin/pint
```

CI will reject PRs that fail Pint checks. Run Pint before pushing.

### Key conventions

- **No comments for obvious code.** Only comment when explaining a non-obvious *why* — hidden constraints, workarounds, subtle invariants.
- **No docblocks for trivial methods.** Typed signatures are self-documenting.
- **Single responsibility.** Keep classes focused. Channels handle notifications; the service handles Composer interaction; the command handles CLI I/O.
- **No unnecessary abstractions.** Don't add interfaces or layers unless they're needed now.

---

## Architecture

```
src/
├── Channels/              # Built-in notification channel implementations
├── Commands/              # Artisan commands
├── Contracts/             # Interfaces (NotificationChannel)
├── Data/                  # Value objects / DTOs (Vulnerability)
├── Jobs/                  # Queueable jobs (SendVulnerabilityNotificationsJob)
├── Mail/                  # Mailables (VulnerabilityReport)
├── Models/                # Eloquent models (VulnerabilityRecord)
├── Services/              # Core business logic (ComposerAuditService)
└── ComposerSecurityWatchServiceProvider.php
```

### Adding a new built-in notification channel

1. Create `src/Channels/MyChannel.php` implementing `NotificationChannel`.
2. Add a config entry under `notify.channels` in `config/composer-security-watch.php` with a `class` key pointing to the new channel.
3. Add the channel to `SendVulnerabilityNotificationsJob::BUILT_IN_CHANNELS` as a fallback for configs that omit the `class` key.
4. Write tests in `tests/Unit/Channels/MyChannelTest.php`.
5. Document in `README.md`.

---

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add Teams notification channel
fix: handle empty composer.lock gracefully
chore: bump testbench to v10
docs: clarify schedule configuration examples
test: cover edge case when audit exits with code 2
```

Keep the subject line under 72 characters. Use the body for context when needed.

---

## Releasing (maintainers only)

1. Update `CHANGELOG.md`.
2. Tag the release: `git tag vX.Y.Z && git push origin vX.Y.Z`.
3. GitHub Actions will publish the tag; Packagist auto-updates via webhook.

---

## Questions?

Open a [GitHub Discussion](https://github.com/your-vendor/laravel-csw/discussions) or an issue. We're happy to help.
