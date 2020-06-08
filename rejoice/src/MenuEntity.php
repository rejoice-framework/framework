<?php
/**
 * BasicApp Provides shortcuts to app methods and properties for the user App
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

namespace Prinx\Rejoice;

use Prinx\Utils\Str;

class MenuEntity
{
    protected $app;

    public function validName($name, $error = 'Invalid name')
    {
        if (!Str::isAlphabetic($name, 2)) {
            $this->app->setError($error);
            return false;
        }

        return true;
    }

    /**
     * Used by the Library to retrieve the applications parameters
     *
     * @return array $app_params
     */
    public function appParams()
    {
        return $this->app_params;
    }

    /**
     * The app databases
     * If $name is not provided the `default` database is returned
     */
    public function db($name = '')
    {
        return $this->app->db($name);
    }

    public function setError($error = '')
    {
        $error .= $this->app->error() ? "\n" : '';
        $this->app->setError($error);
    }

    public function error()
    {
        return $this->app->error();
    }

    public function msisdn()
    {
        return $this->app->msisdn();
    }

    public function network()
    {
        return $this->app->network();
    }

    public function channel()
    {
        return $this->app->channel();
    }

    public function sessionSave($name, $value)
    {
        return $this->app->sessionSave($name, $value);
    }

    public function sessionGet($name)
    {
        return $this->app->sessionGet($name);
    }

    public function sessionHas($name)
    {
        return $this->app->sessionHas($name);
    }

    public function sessionId()
    {
        return $this->app->sessionId();
    }

    public function userResponse()
    {
        return $this->app->userResponse();
    }

    public function userPreviousResponses($menu_id = null)
    {
        return $this->app->userPreviousResponses($menu_id);
    }

    public function appRequestType()
    {
        return $this->app->appRequestType();
    }

    public function sendSms(string $sms, $msisdn = '', $sender_name = '')
    {
        return $this->app->sendSms($sms, $msisdn, $sender_name);
    }

    public function sendSmsAndExit($sms)
    {
        $this->app->sendSms($sms);
        exit;
    }

    public function softEnd($msg)
    {
        if ($this->app->isUssdChannel() &&
            !$this->app->appParams()['allow_timeout'] &&
            $this->app->appParams()['cancel_msg']
        ) {
            $temp = $msg . "\n\n" . $this->app->appParams()['cancel_msg'];

            $msg = $this->app->contentOverflows($temp) ? $msg : $temp;
        }

        return $this->app->softEnd($msg);
    }

    public function hardEnd($msg)
    {
        return $this->app->hardEnd($msg);
    }

    public function setApp(Kernel $app)
    {
        $this->app = $app;
    }

    public function modifyCurrentPageActions(array $actions)
    {
        return $this->app->modifyCurrentPageActions($actions);
    }

    public function currentMenuId()
    {
        return $this->app->currentMenuId();
    }
}
