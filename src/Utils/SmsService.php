<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Utils;

use Rejoice\Foundation\Kernel;

/**
 * Handles SMS related actions.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class SmsService
{
    protected $app;

    /**
     * SMS payload. Consists of the message to send, the sender name and the recipient number.
     *
     * @var array
     */
    protected $data;

    public function __construct(Kernel $app)
    {
        $this->app = $app;
    }

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
    public function send($message, $msisdn = '', $senderName = '', $endpoint = '')
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }

        if (!($message = trim($message))) {
            return;
        }

        $recipient = trim($msisdn ?: $this->app->msisdn());
        $sender = trim($senderName ?: $this->app->config('app.sms_sender_name'));
        $this->data = compact('recipient', 'sender', 'message');

        $endpoint = $endpoint ?: $this->app->config('app.sms_endpoint');
        $response = $this->callSmsApi($this->data, $endpoint);

        $this->handleResponse($response);
    }

    public function handleResponse($response)
    {
        $warnings = [];

        if ($response['error']) {
            $warnings['curl_error'] = $response['error'];
        }

        $result = $response['data'];

        if (isset($result['error']) && $result['error'] === false) {
            $warnings['sms_response'] = $result;
            $warnings['sms_data'] = $this->data;
        }

        if (!empty($warnings)) {
            $this->app->response()->addWarningInSimulator($warnings);
        }
    }

    public function callSmsApi($postvars, $endpoint)
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $endpoint);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postvars);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curlHandle);
        $err = curl_error($curlHandle);

        curl_close($curlHandle);

        return [
            'data' => $result,
            'error' => $err,
        ];
    }
}
