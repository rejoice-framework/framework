<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice\Foundation;

use function Prinx\Dotenv\env;

/**
 * Validate the request parameters.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class RequestValidator extends Validator
{

    public function validate()
    {
        $this->validateRequestParams();
        $this->validateShortcodeIfRequestInit();
    }

    protected function validateShortcodeIfRequestInit()
    {
        if (
            $this->app->ussdRequestType() === APP_REQUEST_INIT &&
            $this->app->config('app.validate_ussd_code')
        ) {
            $ussdCodeCorrect = $this->validateShortcode(
                $this->app->userResponse(),
                env('USSD_CODE', null)
            );

            if (!$ussdCodeCorrect) {
                $this->app->response()->addWarningInSimulator(
                    'INVALID USSD_CODE <strong>' . $this->app->userResponse() .
                    '</strong><br/>Use the ussd code defined in the .env file.'
                );

                $this->app->response()->hardEnd('INVALID USSD_CODE');
            }
        }
    }

    public function validateShortcode(
        $sent_ussdCode,
        $defined_ussdCode
    ) {
        if (null === $defined_ussdCode) {
            $this->app->fail('No "USSD_CODE" value found in the `.env` file. Kindly specify the USSD_CODE variable in the `.env` file.');
        }

        if ($sent_ussdCode !== $defined_ussdCode) {
            return false;
        }

        return true;
    }

    public function validateRequestParams()
    {
        $requestParams = $this->app->request()->input();
        if (!is_array($requestParams)) {
            $this->app->fail('Invalid request parameters received.');
        }

        foreach (REQUIRED_REQUEST_PARAMS as $value) {
            if (!isset($requestParams[$value])) {
                $this->app->fail("'" . $value . "' is missing in the request parameters.");
            }
        }

        if (
            isset($requestParams['channel']) &&
            !in_array($requestParams['channel'], ALLOWED_REQUEST_CHANNELS)
        ) {
            $this->app->fail("Invalid parameter 'channel'.");
        }
    }

}
