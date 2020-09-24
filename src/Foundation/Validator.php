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

require_once __DIR__.'/../../constants.php';

/**
 * Framework's base validator.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Validator
{
    protected $app;

    public function __construct(Kernel $app)
    {
        $this->app = $app;
    }

    public function validateStringParam(
        $param,
        $paramName,
        $pattern = '/[a-z][a-z0-9]+/i',
        $maxLength = 126,
        $minLength = 1
    ) {
        if (!is_string($param)) {
            $this->app->fail('The parameter "'.$paramName.'" must be a string.');
        }

        if (strlen($param) < $minLength) {
            $this->app->fail('The parameter "'.$paramName.'" is too short. It must be at least '.$minLength.' character(s).');
        }

        if (strlen($param) > $maxLength) {
            $this->app->fail('The parameter "'.$paramName.'" is too long. It must be at most '.$maxLength.' characters.');
        }

        if (!preg_match($pattern, $param) === 1) {
            $this->app->fail('The parameter "'.$paramName.'" contains unexpected character(s).');
        }

        return true;
    }
}
