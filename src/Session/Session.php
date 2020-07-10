<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice\Session;

use Prinx\Rejoice\Foundation\Kernel;
use Prinx\Utils\Arr;

require_once __DIR__ . '/../constants.php';

/**
 * Handle the USSD Session: save and retrieve the session data from the database
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Session
{
    protected $driver;
    protected $app;
    protected $id;
    protected $msisdn;
    public $data = [];

    public function __construct(Kernel $app)
    {
        $this->app = $app;
        $this->id = $app->sessionId();
        $this->msisdn = $app->msisdn();

        $this->driver = (require realpath(__DIR__ . '/../../../../../') . '/config/session.php')['driver'];
    }

    /**
     * Check if the just retrieved session is a previous session
     *
     * @return boolean
     */
    public function isPrevious()
    {
        return !empty($this->data);
    }

    /**
     * Starts the session
     *
     * @return void
     */
    protected function start()
    {
        switch ($this->app->ussdRequestType()) {
            case APP_REQUEST_INIT:
                if ($this->app->params('always_start_new_session')) {
                    $this->deletePreviousData();
                    $this->data = [];
                } else {
                    $this->data = $this->retrievePreviousData();
                }

                break;

            case APP_REQUEST_USER_SENT_RESPONSE:
                $this->data = $this->retrievePreviousData();
                // var_dump($this->data);
                break;
        }
    }

    protected function deletePreviousData()
    {
        $this->delete();
    }

    /**
     * Delete session data from the storage
     *
     * This methodm leaves untouched the current live session data
     *
     * @return void
     */
    public function delete()
    {
    }

    /**
     * Attempts to retrieve a previous session data from the storage
     *
     * @return void
     */
    public function retrievePreviousData()
    {
    }

    /**
     * Save the session data to the current configured storage
     *
     * @return void
     */
    public function save()
    {
    }

    /**
     * Reset completely the session data, both in live and in the storage
     *
     * @return void
     */
    public function hardReset()
    {
    }

    /**
     * Returns all the session data
     *
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Retrieve a value from the part of the session accessible by the developer
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws Exception If value not found and no default value has been provided
     */
    public function get($key = null, $default = null)
    {
        if (!$key) {
            $this->data[DEVELOPER_SAVED_DATA] = $this->data[DEVELOPER_SAVED_DATA] ?? [];
            return $this->data[DEVELOPER_SAVED_DATA];
        }

        // if (isset($this->data[DEVELOPER_SAVED_DATA][$key])) {
        //     return $this->data[DEVELOPER_SAVED_DATA][$key];
        // }

        $explodedKey = explode('.', $key);

        if (isset($this->data[DEVELOPER_SAVED_DATA][$explodedKey[0]])) {
            return Arr::multiKeyGet($key, $this->data[DEVELOPER_SAVED_DATA]);
        }

        if (\func_num_args() > 1) {
            return $default;
        }

        throw new \Exception('Index "' . $key . '" not found in the session data.');
    }

    /**
     * Set a value into the part of the session accessible by the developer
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        if (!$this->hasMetadata(DEVELOPER_SAVED_DATA)) {
            $this->setMetadata(DEVELOPER_SAVED_DATA, []);
        }

        // $this->data[DEVELOPER_SAVED_DATA][$key] = $value;
        $this->data[DEVELOPER_SAVED_DATA] = Arr::multiKeySet(
            $key, $value, $this->data[DEVELOPER_SAVED_DATA]
        );
    }

    /**
     * Remove a key from the part of the session accessible by the developer
     *
     * Returns true if the key exists and has been removed. False otherwise
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key)
    {
        // if (isset($this->data[DEVELOPER_SAVED_DATA][$key])) {
        //     unset($this->data[DEVELOPER_SAVED_DATA][$key]);

        //     return true;
        // }

        $explodedKey = explode('.', $key);

        if (isset($this->data[DEVELOPER_SAVED_DATA][$explodedKey[0]])) {
            if (count($explodedKey) === 1) {
                unset($this->data[DEVELOPER_SAVED_DATA][$key]);
            } else {
                Arr::multiKeyRemove($key, $this->data[DEVELOPER_SAVED_DATA]);
            }

            return true;
        }

        return false;
    }

    /**
     * Check if a variable has been saved by the developer in the session
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->data[DEVELOPER_SAVED_DATA][$key]);
    }

    /**
     * Check if a framework-level variable is in the session
     *
     * @param string $key
     * @return boolean
     */
    public function hasMetadata($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Set a framework-level variable in the session
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setMetadata($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Remove a framework-level variable from the session
     *
     * Returns true if the variable exists and has been removed, false otherwise
     *
     * @param string $key
     * @return boolean
     */
    public function removeMetadata($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }

        return false;
    }

    /**
     * Retrieve a framework-level variable from the session
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws Exception If value not found and no default value has been provided
     */
    public function metadata($key = null, $default = null)
    {
        if (!$key) {
            return $this->data;
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        if (\func_num_args() > 1) {
            return $default;
        }

        throw new \Exception('Index "' . $key . '" not found in the session.');
    }

    /**
     * Reset the session
     *
     * This does not affect the session in its storage. Only the live session
     * currently in use. To delete the session completely, in live and in the
     * storage, use `hardReset`
     *
     * @return void
     */
    public function reset()
    {
        $this->data = [];
    }
}
