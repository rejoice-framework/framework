<?php

namespace Rejoice\Menu\Traits;

/**
 * Base menu methods relative to the USSD response.
 */
trait Response
{
    /**
     * Instance of the response to send back to the user.
     *
     * @return Response
     */
    public function response()
    {
        return $this->app->response();
    }

    /**
     * Convert message to string if it is not.
     *
     * @param string|array $message
     *
     * @return string
     */
    public function getStringMessage($message)
    {
        return is_array($message) ? implode("\n", $message) : $message;
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string|array $message
     *
     * @return void
     */
    public function softEnd($message)
    {
        $message = $this->getStringMessage($message);

        if (
            $this->app->isUssdChannel() &&
            !$this->app->config('app.allow_timeout') &&
            $this->app->config('menu.cancel_message')
        ) {
            $sep = $this->app->config('menu.seperator_menu_string_and_cancel_message');
            $temp = $message.$sep.$this->app->config('menu.cancel_message');

            $message = $this->willOverflowWith($temp) ? $message : $temp;
        }

        return $this->response()->softEnd($message);
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string|array $message
     *
     * @return void
     */
    public function respond($message)
    {
        $this->softEnd($message);
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string|array $message
     *
     * @return void
     */
    public function respondAndContinue($message)
    {
        $this->respond($message);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string|array $message
     *
     * @return void
     */
    public function hardEnd($message)
    {
        $message = $this->getStringMessage($message);

        return $this->response()->hardEnd($message);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string[]|string $message
     *
     * @return void
     */
    public function respondAndExit($message)
    {
        $this->hardEnd($message);
    }

    /**
     * Sends final response and the same response as SMS.
     *
     * @param string[]|string $message
     *
     * @return void
     */
    public function respondWithSms($message)
    {
        $this->respondAndContinue($message);
        $this->sendSms();
    }

    /**
     * Sends final response and the same response as SMS and exit.
     *
     * @param string[]|string $message
     *
     * @return void
     */
    public function respondWithSmsAndExit($message)
    {
        $this->respondAndContinue($message);
        $this->sendSmsAndExit($message);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string|array $message
     *
     * @return void
     */
    public function terminate($message)
    {
        $this->hardEnd($message);
    }

    public function willOverflowWith($message)
    {
        return $this->app->menus()->willOverflowWith($message);
    }
}
