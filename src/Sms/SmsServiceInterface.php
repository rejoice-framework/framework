<?php

namespace Rejoice\Sms;

interface SmsServiceInterface
{
    /**
     * Send SMS.
     *
     * @param string[]|string $message
     * @param string          $msisdn
     * @param string          $senderName
     * @param string          $endpoint
     *
     * @return void
     */
    public function send($message, $msisdn = '', $senderName = '', $endpoint = '');
}
