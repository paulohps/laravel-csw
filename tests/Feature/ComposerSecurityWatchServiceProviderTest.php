<?php

use Illuminate\Console\Scheduling\Schedule;
use LaravelCsw\ComposerSecurityWatchServiceProvider;

it('registers csw:audit with the scheduler when enabled', function (): void {
    // Provider booted with enabled=true (default). Resolving Schedule fires
    // the callAfterResolving callback, covering lines 47-48 of the ServiceProvider.
    $schedule = app(Schedule::class);

    $commands = collect($schedule->events())->map(fn ($e) => $e->command);

    expect($commands->contains(fn ($cmd) => str_contains($cmd, 'csw:audit --notify')))->toBeTrue();
});

it('uses the configured cron frequency for the scheduled command', function (): void {
    config(['composer-security-watch.schedule.frequency' => '0 6 * * 1']);

    $schedule = app(Schedule::class);

    $event = collect($schedule->events())
        ->first(fn ($e) => str_contains($e->command, 'csw:audit --notify'));

    expect($event?->expression)->toBe('0 6 * * 1');
});

it('does not register the schedule when disabled', function (): void {
    // Resolve Schedule first so the original (enabled) callback fires and we have
    // a baseline count, then verify a disabled provider adds nothing.
    $schedule = app(Schedule::class);
    $countBefore = count($schedule->events());

    config(['composer-security-watch.enabled' => false]);

    // packageBooted() with enabled=false hits the early return on line 43.
    // Since Schedule is already resolved, callAfterResolving would fire immediately
    // if registered — so the count would grow. The early return prevents that.
    (new ComposerSecurityWatchServiceProvider(app()))->packageBooted();

    expect(count($schedule->events()))->toBe($countBefore);
});
