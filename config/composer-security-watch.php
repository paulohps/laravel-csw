<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable
    |--------------------------------------------------------------------------
    |
    | When disabled, the scheduled audit command will not be registered,
    | effectively pausing all automated security checks.
    |
    */

    'enabled' => env('CSW_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Schedule Frequency
    |--------------------------------------------------------------------------
    |
    | Defines how often the audit command runs automatically via the scheduler.
    | Uses standard cron syntax: "minute hour day-of-month month day-of-week"
    |
    | Examples:
    |   '0 9 * * *'  — Every day at 9:00 AM (default)
    |   '0 9 * * 1'  — Every Monday at 9:00 AM
    |   '0 9 1 * *'  — First day of every month at 9:00 AM
    |
    | Step values (e.g. every 6 hours, every 30 minutes) use the star/N syntax.
    |
    */

    'schedule' => [
        'frequency' => env('CSW_SCHEDULE_FREQUENCY', '0 9 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure the channels through which vulnerability alerts are delivered.
    | Each channel can be individually enabled or disabled.
    |
    | To add a custom channel, implement LaravelCsw\Contracts\NotificationChannel
    | and add an entry with the 'class' key pointing to your implementation.
    |
    */

    'notify' => [

        /*
        |--------------------------------------------------------------------------
        | Notification Job
        |--------------------------------------------------------------------------
        |
        | The job class responsible for dispatching notifications to all enabled
        | channels. Override 'class' with your own implementation if you need
        | custom dispatch logic. The 'queue' key controls which queue the job
        | is sent to (defaults to the 'default' queue).
        |
        */

        'job' => [
            'class' => \LaravelCsw\Jobs\SendVulnerabilityNotificationsJob::class,
            'queue' => env('CSW_JOB_QUEUE', 'default'),
        ],

        'channels' => [

            'log' => [
                'enabled' => env('CSW_NOTIFY_LOG', true),
                'class' => \LaravelCsw\Channels\LogChannel::class,
            ],

            'slack' => [
                'enabled' => env('CSW_NOTIFY_SLACK', false),
                'class' => \LaravelCsw\Channels\SlackChannel::class,

                // Slack Incoming Webhook URL.
                // Create one at: https://api.slack.com/messaging/webhooks
                'webhook_url' => env('CSW_SLACK_WEBHOOK_URL'),
            ],

            'discord' => [
                'enabled' => env('CSW_NOTIFY_DISCORD', false),
                'class' => \LaravelCsw\Channels\DiscordChannel::class,

                // Discord Webhook URL.
                // Create one via Server Settings > Integrations > Webhooks.
                'webhook_url' => env('CSW_DISCORD_WEBHOOK_URL'),
            ],

            'email' => [
                'enabled' => env('CSW_NOTIFY_EMAIL', false),
                'class' => \LaravelCsw\Channels\EmailChannel::class,

                // Recipient(s) for vulnerability reports.
                // Accepts a single address string or an array of addresses.
                'to' => env('CSW_EMAIL_TO'),

                // Mailable class used to build the email.
                // Override with your own Mailable to customise subject, headers, or template.
                // The constructor must accept array $vulnerabilities as its first argument.
                'mailable' => \LaravelCsw\Mail\VulnerabilityReport::class,
            ],

            /*
             | The database channel is disabled by default and requires a
             | migration to be published and run before use.
             |
             | Run: php artisan csw:install-database-channel
             |
             */
            'database' => [
                'enabled' => env('CSW_NOTIFY_DATABASE', false),
                'class' => \LaravelCsw\Channels\DatabaseChannel::class,
            ],

            /*
             | Custom channels — implement LaravelCsw\Contracts\NotificationChannel
             | and register your class here.
             |
             | Example:
             |   'my_channel' => [
             |       'enabled' => true,
             |       'class'   => \App\Security\MyCustomChannel::class,
             |   ],
             |
             */

        ],

    ],

];
