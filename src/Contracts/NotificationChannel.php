<?php

namespace LaravelCsw\Contracts;

use LaravelCsw\Data\Vulnerability;

interface NotificationChannel
{
    /**
     * Send vulnerability notifications through this channel.
     *
     * @param  Vulnerability[]  $vulnerabilities
     */
    public function send(array $vulnerabilities): void;
}
