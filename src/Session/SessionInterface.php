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

/**
 * Provides an interface to respect by any created session driver.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
interface SessionInterface
{
    public function hasPrevious();

    /**
     * Delete session data only from the storage.
     *
     * Does not delete the current live session data.
     *
     * @return void
     */
    public function delete();

    public function reset();

    /**
     * Reset completely the session data, both in live and in the storage.
     *
     * @return void
     */
    public function hardReset();

    /**
     * Attempts to retrieve a previous session data from the storage.
     *
     * @return void
     */
    public function retrievePreviousData();

    public function retrieveData();

    public function previousSessionNotExists();

    /**
     * Save the session data to the current configured storage.
     *
     * @return void
     */
    public function save();

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
    public function get($key = null, $default = null);

    /**
     * Set a value into the part of the session accessible by the developer.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, $value);

    /**
     * Remove a key from the part of the session accessible by the developer.
     *
     * Returns true if the key exists and has been removed. False otherwise
     *
     * @param string $key
     *
     * @return bool
     */
    public function remove($key);

    /**
     * Returns the session value of `$key` if `$key` is in the session, else returns `$default` and
     * save `$default` to the session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function remember($key, $default);
}
