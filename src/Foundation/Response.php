<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Foundation;

/**
 * Handles the response to send back to the user.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Response
{
    protected $simulatorMetadata = ['info', 'warning', 'error'];

    protected $errorInSimulator = [];

    protected $warningInSimulator = [];

    protected $infoInSimulator = [];

    protected $app;

    public function __construct(Kernel $app)
    {
        $this->app = $app;
    }

    public function hardEnd($message = '')
    {
        $this->end($message);
    }

    public function softEnd($message = '')
    {
        $this->end($message, false);
    }

    public function end($sentMsg = '', $hard = true)
    {
        $this->app->setResponseAlreadySentToUser(true);
        $message = $sentMsg !== '' ? $sentMsg : $this->app->config('menu.default_end_message');
        $this->sendLast($message, $hard);
    }

    public function sendLast($message, $hard = false)
    {
        $requestType = $this->appHasTimedOut() ? APP_REQUEST_ASK_USER_RESPONSE : APP_REQUEST_END;

        $this->send($message, $requestType, $hard);

        $this->app->session()->hardReset();
    }

    public function appHasTimedOut()
    {
        return
        $this->app->isUssdChannel() &&
        $this->app->session()->mustNotTimeout() &&
        $this->app->session()->hasTimedOut();
    }

    public function send(
        $message,
        $ussdRequestType = APP_REQUEST_ASK_USER_RESPONSE,
        $hard = false
    ) {
        $previouslyDisplayed = trim(ob_get_clean());
        ob_start();
        $error = error_get_last();

        if ($previouslyDisplayed && !$error) {
            $this->addInfoInSimulator("\n".$previouslyDisplayed."\n");
        } elseif ($error) {
            $this->addErrorInSimulator($this->formatError($error));
            $this->addErrorInSimulator($previouslyDisplayed);

            $appFailMessage = $this->app->config('menu.application_failed_message');

            if ($message !== $appFailMessage) {
                $this->addInfoInSimulator("RESPONSE:\n\n$message");
            }

            $hard = true;
            $ussdRequestType = APP_REQUEST_END;
            $message = $appFailMessage;
        }

        $response = $this->format($message, $ussdRequestType);

        echo $response;

        header('Content-Encoding: none');
        header('Content-Length: '.ob_get_length());
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();

        if ($hard) {
            exit;
        }
    }

    public function formatError(array $error)
    {
        $errorStr = $error['message'];
        $errorStr .= "\nIn ".$error['file'].' at line '.$error['line'];
        $errorStr .= "\nError type ".$error['type'];

        return $errorStr;
    }

    protected function format($message, $requestType)
    {
        $messageParam = $this->app->config('app.request_param_menu_string');
        $ussdServiceOpParam = $this->app->config('app.request_param_request_type');
        $sessionIDParam = $this->app->config('app.request_param_session_id');

        $fields = [
            $messageParam       => trim($message),
            $ussdServiceOpParam => $requestType,
            $sessionIDParam     => $this->app->sessionId(),
        ];

        foreach ($this->simulatorMetadata as $metadata) {
            if ($this->{$metadata.'InSimulator'}) {
                $fields[$metadata] = $this->{$metadata.'InSimulator'};
            }
        }

        return json_encode($fields);
    }

    public function sendRemote($resJSON)
    {
        $response = json_decode($resJSON, true);

        if (!is_array($response)) {
            echo 'ERROR OCCURED AT THE REMOTE USSD SIDE:  '.$resJSON;

            return;
        }

        echo $resJSON;
    }

    public function setInSimulator(array $data, string $type)
    {
        $this->{$type.'InSimulator'} = $data;
    }

    public function addInSimulator($data, string $type)
    {
        if (is_array($data)) {
            array_merge($this->{$type.'InSimulator'}, $data);
        } else {
            array_push($this->{$type.'InSimulator'}, $data);
        }
    }

    public function setInfoInSimulator(array $info)
    {
        $this->setInSimulator($info, 'info');
    }

    public function addInfoInSimulator($info)
    {
        $this->addInSimulator($info, 'info');
    }

    public function setWarningInSimulator(array $warn)
    {
        $this->setInSimulator($warn, 'warning');
    }

    public function addWarningInSimulator($warn)
    {
        $this->addInSimulator($warn, 'warning');
    }

    public function setErrorInSimulator(array $error)
    {
        $this->setInSimulator($error, 'error');
    }

    public function addErrorInSimulator($error)
    {
        $this->addInSimulator($error, 'error');
    }
}
