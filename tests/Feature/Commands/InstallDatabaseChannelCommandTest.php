<?php

it('publishes migrations and shows next steps', function (): void {
    $this->artisan('csw:install-database-channel')
        ->expectsOutput('Migration published successfully.')
        ->assertExitCode(0);
});
