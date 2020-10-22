<?php

if (!function_exists('project_root')) {
    /**
     * Project root directory.
     *
     * @param  string   $append
     * @return string
     */
    function project_root($append = '')
    {
        return Rejoice\Foundation\Path::toProject($append);
    }
}
if (!function_exists('app_path')) {
    /**
     * Project app directory.
     *
     * @param  string   $append
     * @return string
     */
    function app_path($append = '')
    {
        return Rejoice\Foundation\Path::toProject($append);
    }
}

if (!function_exists('config_path')) {
    /**
     * Project config directory.
     *
     * @param  string   $append
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
     * @param  string   $append
     * @return string
     */
    function resource_path($append = '')
    {
        return Rejoice\Foundation\Path::toStorage($append);
    }
}

if (!function_exists('public_path')) {
    /**
     * Project public directory.
     *
     * @param  string   $append
     * @return string
     */
    function public_path($append = '')
    {
        return Rejoice\Foundation\Path::toStorage($append);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Project storage directory.
     *
     * @param  string   $append
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
     * @param  string   $append
     * @return string
     */
    function test_path($append = '')
    {
        return Rejoice\Foundation\Path::toConfig($append);
    }
}
