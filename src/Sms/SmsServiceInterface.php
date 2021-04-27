<?php

namespace Rejoice\Sms;

interface SmsServiceInterface
{
    /**
     * Send SMS.
     *
     * @param string[]|string $message
     *
     * @return void
     */
    public function send($message, string $msisdn = '', string $senderName = '', string $endpoint = '');
}
