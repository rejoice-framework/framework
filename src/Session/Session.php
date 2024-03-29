<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Session;

use Prinx\Arr;
use Rejoice\Foundation\Kernel;

require_once __DIR__.'/../../constants.php';

/**
 * Handle the USSD Session: save and retrieve the session data from the database.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
abstract class Session implements SessionInterface
{
    protected $driver;
    protected $app;
    protected $id;
    protected $msisdn;
    protected $config;
    protected $isNew = true;
    public $data = [];

    public function __construct(Kernel $app)
    {
        $this->app = $app;
        $this->id = $app->sessionId();
        $this->msisdn = $app->msisdn();
        $this->config = require $app->path('app_session_config_file');
        $this->driver = $this->config['driver'];
    }

    /**
     * Starts the session.
     *
     * @return void
     */
    protected function start()
    {
        $this->data = $this->retrievePreviousData();
        $this->isNew = empty($this->data);

        if ($this->isNew()) {
            $this->initialise();
        } elseif ($this->app->isFirstRequest() && $this->hasExpired()) {
            $this->renew();
        }
    }

    /**
     * Check if the session loaded is a new session.
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    public function initialise()
    {
        $this->data = [
            '__last_connection' => microtime(true),
        ];
    }

    public function hasExpired()
    {
        if ($this->app->config('app.always_start_new_session')) {
            return true;
        }

        return $this->hasPassed('lifetime');
    }

    public function hasPassed($type)
    {
        $allowed = $this->app->config("session.{$type}");
        $lastConnection = $this->data['__last_connection'] ?? 0;
        $now = microtime(true);

        return ($now - $lastConnection) >= $allowed;
    }

    public function renew()
    {
        $this->deletePreviousData();
        $this->isNew = true;
        $this->initialise();
    }

    protected function deletePreviousData()
    {
        $this->delete();
    }

    /**
     * Check if the just retrieved session is a previous session.
     *
     * @return bool
     */
    public function hasPrevious()
    {
        return !$this->isNew();
    }

    public function hasTimedOut()
    {
        return $this->hasPassed('timeout');
    }

    public function mustNotTimeout()
    {
        return $this->app->config('app.allow_timeout') === false;
    }

    /**
     * Reset the session.
     *
     * This does not affect the session in its storage. Only the live session
     * currently in use. To delete the session completely, in live and in the
     * storage, use `hardReset`
     *
     * @return void
     */
    public function reset()
    {
        $this->initialise();
    }

    /**
     * Returns all the session data.
     *
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Retrieve a value from the part of the session accessible by the developer.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @throws \RuntimeException If value not found and no default value has been provided
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (!$key) {
            $this->data[DEVELOPER_SAVED_DATA] = $this->data[DEVELOPER_SAVED_DATA] ?? [];

            return $this->data[DEVELOPER_SAVED_DATA];
        }

        $explodedKey = explode('.', $key);

        if (isset($this->data[DEVELOPER_SAVED_DATA][$explodedKey[0]])) {
            return Arr::multiKeyGet($key, $this->data[DEVELOPER_SAVED_DATA]);
        }

        if (\func_num_args() > 1) {
            return $default;
        }

        throw new \RuntimeException('Index "'.$key.'" not found in the session data.');
    }

    /**
     * Set a value into the part of the session accessible by the developer.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, $value)
    {
        if (!$this->hasMetadata(DEVELOPER_SAVED_DATA)) {
            $this->setMetadata(DEVELOPER_SAVED_DATA, []);
        }

        if (is_callable($value)) {
            $value = call_user_func($value);
        }

        $this->data[DEVELOPER_SAVED_DATA] = Arr::multiKeySet(
            $key,
            $value,
            $this->data[DEVELOPER_SAVED_DATA]
        );

        return $this;
    }

    /**
     * Returns the session value of `$key` if `$key` is in the session, else returns `$default` and
     * save `$default` to the session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function remember($key, $default)
    {
        if (!$this->has($key)) {
            $this->set($key, $default);
        }

        return $this->get($key);
    }

    /**
     * Remove a key from the part of the session accessible by the developer.
     *
     * Returns true if the key exists and has been removed. False otherwise
     *
     * @param string $key
     *
     * @return bool
     */
    public function remove($key)
    {
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
     * Check if a variable has been saved by the developer in the session.
     *
     * If no key is passed, checks if the session is not empty
     *
     * @return bool
     */
    public function has(string $key = '')
    {
        if (!$key) {
            return !empty($this->data[DEVELOPER_SAVED_DATA]);
        }

        return isset($this->data[DEVELOPER_SAVED_DATA][$key]);
    }

    /**
     * Check if a particular framework-level variable exists in the session.
     *
     * If no key is passed, checks if the session is not empty
     *
     * @return bool
     */
    public function hasMetadata(string $key = '')
    {
        if (!$key) {
            return !empty($this->data);
        }

        return isset($this->data[$key]);
    }

    /**
     * Set a framework-level variable in the session.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setMetadata(string $key, $value)
    {
        $this->data = Arr::multiKeySet($key, $value, $this->data);

        return $this;
    }

    /**
     * Remove a framework-level variable from the session.
     *
     * Returns true if the variable exists and has been removed, false otherwise
     *
     * @return void
     */
    public function removeMetadata(string $key)
    {
        Arr::multiKeyRemove($key, $this->data);
    }

    /**
     * Retrieve a framework-level variable from the session.
     *
     * @param mixed $default
     *
     * @throws \RuntimeException If value not found and no default value has been provided
     *
     * @return mixed
     */
    public function metadata(string $key = '', $default = null)
    {
        if (!$key) {
            return $this->data;
        }

        $value = Arr::multiKeyGet($key, $this->data);

        if (null === $value) {
            if (\func_num_args() > 1) {
                return $default;
            }

            throw new \RuntimeException('Index "'.$key.'" not found in the session.');
        }

        return $value;
    }
}
