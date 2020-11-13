<?php

namespace Rejoice\Menu\Traits;

/**
 * Base menu methods relative to sms.
 */
trait Sms
{
    /**
     * Send SMS to a number.
     *
     * If the phone number  (`$tel`) has not been passed, the SMS will be sent to the
     * current user (`$this->tel()`)
     *
     * If the `senderName` has not been passed, the method will try to use
     * any configured SMS_SENDER_NAME variable in the env file or the equivalent
     * parameter in the config/app.php file (`sms_sender_name`). If this parameter
     * is not found, the sms will just be discarded.
     *
     * If the `endpoint` has not been passed, the method will try to use any configured
     * SMS_ENDPOINT variable in the env file or the equivalent parameter in the config/app.
     * php file (`sms_endpoint`). If this parameter is not found, the sms will just be
     * discarded.
     *
     * @param string $sms      The text to send
     * @param string $tel      The phone number to send the SMS to.
     * @param string $sender   The name that will appear as the one who sent the SMS.
     * @param string $endpoint The endpoint to send the SMS to.
     *
     * @return void
     */
    public function sendSms($sms, $tel = '', $sender = '', $endpoint = '')
    {
        $this->app->sendSms($sms, $tel, $sender, $endpoint);
    }

    /**
     * Send SMS and terminate the application.
     *
     * @param string $sms      The text to send
     * @param string $tel      The phone number to send the SMS to.
     * @param string $sender   The name that will appear as the one who sent the SMS.
     * @param string $endpoint The endpoint to send the SMS to.
     *
     * @return void
     */
    public function sendSmsAndExit($sms, $tel = '', $sender = '', $endpoint = '')
    {
        $this->app->sendSms($sms, $tel, $sender, $endpoint);
        exit;
    }
}
