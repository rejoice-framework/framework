<?php
/**
 * Handle the USSD Session: save and retrieve the session data from the database
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 * @license MIT
 */

namespace Prinx\Rejoice;

require_once 'constants.php';

class Session
{
    protected $driver;
    protected $ussd_lib;

    protected $id;
    protected $msisdn;
    protected $data;

    public function __construct($ussd_lib)
    {
        $this->ussd_lib = $ussd_lib;
        $this->id = $ussd_lib->sessionId();
        $this->msisdn = $ussd_lib->msisdn();

        $this->driver = (require realpath(__DIR__ . '/../../../../config/session.php'))['driver'];
    }

    public function isPrevious()
    {
        return !empty($this->data);
    }

    protected function start()
    {
        switch ($this->ussd_lib->ussdRequestType()) {
            case APP_REQUEST_INIT:
                if ($this->ussd_lib->appParams()['always_start_new_session']) {
                    $this->deletePreviousData();
                    $this->data = [];
                } else {
                    $this->data = $this->retrievePreviousData();
                }

                break;

            case APP_REQUEST_USER_SENT_RESPONSE:
                $this->data = $this->retrievePreviousData();
                break;
        }
    }

    protected function deletePreviousData()
    {
        $this->delete();
    }

    public function data()
    {
        return $this->data;
    }

    public function get($key, $default = null)
    {
        if (isset($this->data[DEVELOPER_SAVED_DATA][$key])) {
            return $this->data[DEVELOPER_SAVED_DATA][$key];
        }

        return $default;
    }

    public function set($key, $value)
    {
        $this->data[DEVELOPER_SAVED_DATA][$key] = $value;
    }
}
