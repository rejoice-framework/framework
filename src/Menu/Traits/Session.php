<?php

namespace Rejoice\Menu\Traits;

/**
 * Base menu methods relative to session.
 */
trait Session
{
    /**
     * Allows developer to save a value in the session. This does not persist the value to storage
     * automatically. The value is persisted only at the end of the request (when the framework
     * sends a response back to the user.).
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
     * Allows developer to retrieve a previously saved value from the session.
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
     * Allows developer to check if the session contains an index.
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
     * Allows the developer to remove a key from the session.
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
     * Returns the session value of `$key` if `$key` is in the session, else returns `$default` and
     * save `$default` to the session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function sessionRemember($key, $default)
    {
        return $this->session()->remember($key, $default);
    }

    /**
     * Get the session instance. Can also be used as `sessionGet` by passing $key with/out $default.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @throws \RuntimeException If $key not found and no $default passed.
     *
     * @return \Rejoice\Session\SessionInterface|mixed
     */
    public function session($key = null, $default = null)
    {
        return $this->app->session($key, $default);
    }
}
