<?php

if (!function_exists('project_root')) {
    /**
     * Project root directory.
     *
     * @param string $append
     *
     * @return string
     */
    function project_root($append = '')
    {
        return Rejoice\Foundation\Path::toProject($append);
    }
}

if (!function_exists('root_path')) {
    /**
     * Project root directory.
     *
     * @param string $append
     *
     * @return string
     */
    function root_path($append = '')
    {
        return Rejoice\Foundation\Path::toProject($append);
    }
}

if (!function_exists('app_path')) {
    /**
     * Project app directory.
     *
     * @param string $append
     *
     * @return string
     */
    function app_path($append = '')
    {
        return Rejoice\Foundation\Path::toApp($append);
    }
}

if (!function_exists('config_path')) {
    /**
     * Project config directory.
     *
     * @param string $append
     *
     * @return string
     */
    function config_path($append = '')
    {
        return Rejoice\Foundation\Path::toConfig($append);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Project resource directory.
     *
     * @param string $append
     *
     * @return string
     */
    function resource_path($append = '')
    {
        return Rejoice\Foundation\Path::toResources($append);
    }
}

if (!function_exists('public_path')) {
    /**
     * Project public directory.
     *
     * @param string $append
     *
     * @return string
     */
    function public_path($append = '')
    {
        return Rejoice\Foundation\Path::toPublic($append);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Project storage directory.
     *
     * @param string $append
     *
     * @return string
     */
    function storage_path($append = '')
    {
        return Rejoice\Foundation\Path::toStorage($append);
    }
}

if (!function_exists('test_path')) {
    /**
     * Project tests directory.
     *
     * @param string $append
     *
     * @return string
     */
    function test_path($append = '')
    {
        return Rejoice\Foundation\Path::toTests($append);
    }
}

if (!function_exists('vendor_path')) {
    /**
     * Project vendor directory.
     *
     * @param string $append
     *
     * @return string
     */
    function vendor_path($append = '')
    {
        return Rejoice\Foundation\Path::toVendor($append);
    }
}

if (!function_exists('is_associative')) {
    /**
     * Checks if a variable is an associative array.
     *
     * @param mixed $var
     *
     * @return bool
     */
    function is_associative($var = [])
    {
        if (!is_array($var)) {
            return false;
        }

        foreach ($var as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('str')) {
    /**
     * Convert native string to unicode string object.
     * This allows to use various string method on the string.
     *
     * @param string $string
     *
     * @return \Symfony\Component\String\UnicodeString
     */
    function str(string $string)
    {
        return \Symfony\Component\String\u($string);
    }
}
