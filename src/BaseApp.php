<?php
/**
 * BaseApp Provides shortcuts to app methods and properties for the user App
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

namespace Prinx\Rejoice;

class BaseApp
{
    // Optional parameters to enable if needed.
    // Copy the needed line into the `app_params` array below
    /*
    'id'                                  => '',
    'sms_sender_name'                     => '',
    'connect_app_db'                      => false,
    'validate_shortcode'                  => false,
    'always_send_sms_at_end'              => false,
    'always_start_new_session'            => false,
    'ask_user_before_reload_last_session' => true,
    'no_timeout_final_response'           => true,
    'environment' => 'dev',
    'sms_endpoint' => '',

    'end_on_user_error' => false,
    'end_on_unhandled_action' => false,

    'back_action_thrower' => '0',
    'back_action_display' => 'Back',
    'splitted_menu_next_thrower' => '99',
    'splitted_menu_display' => 'More',

    'default_end_msg' => 'Thank you.',
    'default_error_msg' => 'Invalid Input.',
    'no_timeout_final_response_cancel_msg' => 'Press Cancel to end.',
     */
    protected $app_params = [];

    protected $app;

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
        $this->app->setError($error);
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

    public function userPreviousResponses($menu_name = null)
    {
        return $this->app->userPreviousResponses($menu_name);
    }

    public function appRequestType()
    {
        return $this->app->appRequestType();
    }

    public function sendSms($sms)
    {
        return $this->app->sendSms($sms);
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
}
