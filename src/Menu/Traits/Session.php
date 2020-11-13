<?php

namespace Rejoice\Menu\Traits;

/**
 * Base menu methods relative to session.
 */
trait Session
{
    /**
     * Allows developer to save a value in the session.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function sessionSave($name, $value)
    {
        $this->session()->set($name, $value);
    }

    /**
     * Allow developer to retrieve a previously saved value from the session.
     *
     * Returns the value associated to $name, if found. If the key $name is not
     * in the session, it returns the $default passed. If no $default was
     * passed, it throws an exception.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function sessionGet($name, $default = null)
    {
        return $this->session($name, $default);
    }

    /**
     * Allow developer to check if the session contains an index.
     *
     * @param string $name
     *
     * @return bool
     */
    public function sessionHas($name)
    {
        return $this->session()->has($name);
    }

    /**
     * Allow the developer to remove a key from the session.
     *
     * @param string $name
     *
     * @return void
     */
    public function sessionRemove($name)
    {
        $this->session()->remove($name);
    }

    /**
     * Allow the developer to retrieve a value from the session.
     * This is identical to `sessionGet`.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @throws \RuntimeException If $key not found and no $default passed.
     *
     * @return mixed
     */
    public function session($key = null, $default = null)
    {
        return $this->app->session($key, $default);
    }
}
