<?php
/**
 * MenuEntity Provides shortcuts to app methods and properties for the user App
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

namespace Prinx\Rejoice;

use Prinx\Utils\Str;

class MenuEntity
{
    protected $app;
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function menuName()
    {
        return $this->name;
    }

    public function insertBackOption($option = '0', $display = 'Back')
    {
        $this->modifyCurrentPageActions([
            $option => [
                'display' => $display,
                'next_menu' => '__back',
            ],
        ]);
    }

    public function insertMainMenuOption($option = '00', $display = 'Main menu')
    {
        $this->modifyCurrentPageActions([
            $option => [
                'display' => $display,
                'next_menu' => '__welcome',
            ],
        ]);
    }

    public function currentMenuName()
    {
        return $this->menuName();
    }

    public function validName($name, $error = 'Invalid name')
    {
        if (!Str::isAlphabetic($name, 2)) {
            $this->app->setError($error);
            return false;
        }

        return true;
    }

    /**
     * Used by the Library to retrieve the applications parameters
     *
     * @return array $app_params
     */
    public function appParams()
    {
        return $this->app_params;
    }

    public function setError($error = '')
    {
        $error .= $this->app->error() ? "\n" : '';
        $this->app->setError($error);
    }

    public function sendSmsAndExit($sms)
    {
        $this->app->sendSms($sms);
        exit;
    }

    public function softEnd($msg)
    {
        if ($this->app->isUssdChannel() &&
            !$this->app->appParams()['allow_timeout'] &&
            $this->app->appParams()['cancel_msg']
        ) {
            $temp = $msg . "\n\n" . $this->app->appParams()['cancel_msg'];

            $msg = $this->app->contentOverflows($temp) ? $msg : $temp;
        }

        return $this->app->softEnd($msg);
    }

    public function setApp(Kernel $app)
    {
        $this->app = $app;
    }

    public function __call($method, $args)
    {
        if (method_exists($this->app, $method)) {
            return call_user_func([$this->app, $method], ...$args);
        }

        throw new \Exception('Undefined method `' . $method . '` in class ' . get_class($this));
    }
}
