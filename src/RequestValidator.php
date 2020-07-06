<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice;

require_once 'Validator.php';

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
            $this->app->params('validate_ussd_code')
        ) {
            $ussdCodeCorrect = $this->validateShortcode(
                $this->app->userResponse(),
                env('USSD_CODE', null)
            );

            if (!$ussdCodeCorrect) {
                $this->app->addWarningInSimulator(
                    'INVALID USSD_CODE <strong>' . $this->app->userResponse() .
                    '</strong><br/>Use the ussd code defined in the .env file.'
                );

                $this->app->hardEnd('INVALID USSD_CODE');
            }
        }
    }

    public function validateShortcode(
        $sent_ussdCode,
        $defined_ussdCode
    ) {
        if ($defined_ussdCode === null) {
            exit('No "USSD_CODE" value found in the `.env` file. Kindly specify the ussd code application ussd code in the `.env` file.<br><br>Eg.<br>USSD_CODE=*380*75#');
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
            exit('Invalid request parameters received.');
        }

        foreach (REQUIRED_REQUEST_PARAMS as $value) {
            if (!isset($requestParams[$value])) {
                exit("'" . $value . "' is missing in the request parameters.");
            }
        }

        if (
            isset($requestParams['channel']) &&
            !in_array($requestParams['channel'], ALLOWED_REQUEST_CHANNELS)
        ) {
            exit("Invalid parameter 'channel'.");
        }
    }

}
