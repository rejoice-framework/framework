<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice\Foundation;

use Prinx\Rejoice\Foundation\Kernel;

/**
 * Handles the response to send back to the user
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Response
{
    protected $warningInSimulator = [];

    protected $infoInSimulator = [];

    public function __construct(Kernel $app)
    {
        $this->app = $app;
    }

    public function end($sentMsg = '', $hard = true)
    {
        $this->app->setResponseAlreadySentToUser(true);
        $message = $sentMsg !== '' ? $sentMsg : $this->app->params('default_end_msg');
        $this->sendLast($message, $hard);
    }

    protected function format($message, $requestType)
    {
        $fields = array(
            'message' => trim($message),
            'ussdServiceOp' => $requestType,
            'sessionID' => $this->app->sessionId(),
        );

        if ($this->warningInSimulator) {
            $fields['WARNING'] = $this->warningInSimulator;
        }

        if ($this->infoInSimulator) {
            $fields['INFO'] = $this->infoInSimulator;
        }

        return json_encode($fields);
    }

    public function send(
        $message,
        $ussdRequestType = APP_REQUEST_ASK_USER_RESPONSE,
        $hard = false
    ) {
        /*
         * Sometimes, we need to send the response to the user and do
         * another staff before ending the script. Those times, we just
         * need to echo the response. That is the soft response sending.
         * Sometimes we need to terminate the script immediately when sending
         * the response; for exemple when the developer himself will call the
         * 'hardEnd' method from the 'before' method.
         */
        if ($hard) {
            exit($this->format($message, $ussdRequestType));
        } else {
            /*
             * All these ob_start, ob_flush, etc are just to be able to send the
             * response BUT continue the script (so that the user receive
             * the response faster, as the USSD times out very quickly)
             *
             * ``ignore_user_abort(true);``  Not really needed here
             * (useful if it was in a browser or cgi where the user
             * can abort the request.)
             */
            // ignore_user_abort(true);

            /*
             * ``set_time_limit(0);``
             * In case the script is taking longer than the PHP default
             * execution time limit
             */
            // set_time_limit(0);
            // ob_start();

            $previousDisplayed = ob_get_clean();
            if (error_get_last() === null) {
                $this->addInfoInSimulator($previousDisplayed);
            }

            $response = $this->format($message, $ussdRequestType);

            if (error_get_last() !== null) {
                $response = '<span class="text-danger">APPLICATION ERROR</span><br>' . $previousDisplayed . '<br><br><span class="text-primary">USSD RESPONSE</span><br>' . $response;
            }

            echo $response;

            header('Content-Encoding: none');

            // if (error_get_last() === null) {
            header('Content-Length: ' . ob_get_length());
            // }

            header('Connection: close');
            ob_end_flush();
            ob_flush();
            flush();
        }
    }

    public function sendLast($message, $hard = false)
    {
        if ($this->app->isUssdChannel() && $this->app->mustNotTimeout()) {
            $this->send($message, APP_REQUEST_ASK_USER_RESPONSE, $hard);
        } else {
            $this->send($message, APP_REQUEST_END, $hard);
        }

        $this->app->session()->hardReset();
    }

    public function hardEnd($message = '')
    {
        $this->end($message);
    }

    public function softEnd($message = '')
    {
        $this->end($message, false);
    }

    public function sendRemote($resJSON)
    {
        $response = json_decode($resJSON, true);

        /*
         * Important! To notify the developer that the error occured at
         * the remote ussd side and not at this ussd switch side.
         */
        if (!is_array($response)) {
            echo "ERROR OCCURED AT THE REMOTE USSD SIDE:  " . $resJSON;
            return;
        }

        echo $resJSON;
    }

    public function setWarningInSimulator(array $warn)
    {
        $this->warningInSimulator = $warn;
    }

    public function addWarningInSimulator($warn)
    {
        if (is_array($warn)) {
            array_merge($this->warningInSimulator, $warn);
        } else {
            array_push($this->warningInSimulator, $warn);
        }
    }

    public function setInfoInSimulator(array $info)
    {
        $this->infoInSimulator = $info;
    }

    public function addInfoInSimulator($info)
    {
        if (is_array($info)) {
            array_merge($this->infoInSimulator, $info);
        } else {
            array_push($this->infoInSimulator, $info);
        }
    }
}
