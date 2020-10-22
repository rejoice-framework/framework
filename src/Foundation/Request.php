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

use Prinx\Str;

/**
 * Handles the request to the framework.
 *
 * @todo Replace this request class by \Symfony\Component\HttpFoundation
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Request
{
    protected $query = [];

    protected $input = [
        'channel' => 'USSD',
    ];

    public function __construct(Kernel $app)
    {
        $this->app = $app;
        $this->hydrateInput($app->forcedInput() ? $app->forcedInput() : $_POST);
        $this->hydrateQuery($_GET);
    }

    public function hydrateInput($requestParams)
    {
        $input = [];
        foreach ($requestParams as $param => $value) {
            $input[$param] = $this->sanitize($value);
        }

        $msisdnKey = $this->app->config('app.request_param_user_phone_number');
        if (isset($input[$msisdnKey])) {
            $input[$msisdnKey] = Str::internationaliseNumber(
                $input[$msisdnKey],
                $this->app->config('app.country_phone_prefix')
            );
        }

        if (isset($requestParams['channel'])) {
            $input['channel'] = strtoupper($this->sanitize($requestParams['channel']));
        }

        $this->input = array_merge($this->input, $input);
    }

    public function hydrateQuery($requestParams)
    {
        $query = [];

        foreach ($requestParams as $param => $value) {
            $query[$param] = $this->sanitize($value);
        }

        $this->query = array_merge($this->query, $query);
    }

    /**
     * Returns a request POST parameter.
     *
     * If the parameter was not found and the default value is returned
     *
     * If no parameter has been passed, the array of POST parameters is returned
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        return $this->param($key, $default, $this->input);
    }

    /**
     * Returns a request GET parameter.
     *
     * If the parameter was not found and the default value is returned
     *
     * If no parameter has been passed, the array of GET parameters is returned
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function query($key = null, $default = null)
    {
        return $this->param($key, $default, $this->query);
    }

    public function param($key, $default, $param = [])
    {
        $param = $param ?: array_merge($this->input, $this->query);

        if (!$key) {
            return $param;
        }

        if (!isset($param[$key])) {
            throw new \Exception('Undefined request input `'.$key.'`');
        }

        return $key ? $param[$key] : $default;
    }

    /**
     * Changes the value of a request parameter or enforces a new parameter
     * into the input parameter bag.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function forceInput($name, $value)
    {
        $this->input[$name] = $value;
    }

    public function sanitize($var)
    {
        if (!is_string($var)) {
            return $var;
        }

        // return htmlspecialchars(addslashes(urldecode($var)));

        return htmlspecialchars(urldecode($var));
    }
}
