<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Menu;

use Prinx\Notify\Log;
use Prinx\Os;
use Prinx\Str;
use Rejoice\Foundation\Kernel;
use Rejoice\Menu\Traits\Action;
use Rejoice\Menu\Traits\Pagination;
use Rejoice\Menu\Traits\Response;
use Rejoice\Menu\Traits\Session;
use Rejoice\Menu\Traits\Sms;

/**
 * Provides shortcuts to app methods and properties for the user App.
 *
 * @method void before() Allows you to run a custom script before the menu is displayed to the user.
 * This method runs before every other method of the current menu entity
 *
 * @method string|array message() Returns the message to display at top of the current menu screen.
 * If it returns an array, the indexes of the array will be assumed to be
 * placeholders inside the menu message defined in the menus.php file for this
 * particular menu.
 *
 * @method array actions() Returns the actions of the current menu
 *
 * @method UserResponseValidator|array|string|boolean validate() Validate the user's response. If it returns false, an invalid input error
 * will be sent to the user. You can customize the error by calling the
 * `addError` or `setError` method of the menu entity (Eg: $this->setError("The
 * age must be greater than 5"))
 *
 * @method mixed saveAs() Allows to modify the user's response before saving it in the session.
 *
 * @method void after() Allows you to run a custom script after the menu response has
 * been processed and the response of the user has passed the validation
 *
 * @method void onMoveToNextMenu() Allows you to run a custom script after the menu response
 * has been processed and the user is moving the next screen. The back screen
 * (previous screen), the welcome screen, same screen, paginate screens (back
 * or forward) are not considered as next screen. Hence, this method will not
 * run for them. Instead use the after `method` if you want to consider them.
 *
 * @method mixed onBack() Run when user goes back by using the __back magic menu
 *
 * @method mixed onPaginateForward() Runs when when user moving forward in on a paginable menu
 *
 * @method mixed onPaginateBack() Runs when when user moving back in on a paginable menu
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class BaseMenu/* implements \ArrayAccess */
{
    use Action, Response, Pagination, Session, Sms;

    /**
     * The instance of the application.
     *
     * @var Kernel
     */
    protected $app;

    /**
     * The name of this menu.
     *
     * @var string
     */
    protected $name;

    /**
     * The instance of the logger on this menu.
     *
     * @var Log
     */
    protected $logger;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function msisdn()
    {
        return $this->app->msisdn();
    }

    public function menus()
    {
        return $this->app->menus();
    }

    public function ussdRequestType()
    {
        return $this->app->ussdRequestType();
    }

    /**
     * Retrieve a request input.
     *
     * @param  string  $name
     * @return mixed
     */
    public function request($name = null)
    {
        return $this->app->request($name);
    }

    public function config($key = null, $default = null, $silent = false)
    {
        $args = func_get_args();

        return $this->app->config(...$args);
    }

    /**
     * Returns the phone number of the user.
     *
     * @return string
     */
    public function tel()
    {
        return $this->msisdn();
    }

    /**
     * Log a message to the default log system
     * (storage/logs/{date}/{name_of_this_menu}.log).
     *
     * @param  string|array              $data  Thr data to log
     * @param  string                    $level The log level
     * @throws \UnexpectedValueException If the level passed is unknown
     * @return void
     */
    public function log($data, $level = 'info')
    {
        if (!$this->config('app.log_enabled')) {
            return;
        }

        $this->logger()->log($level, $data);
    }

    /**
     * Returns the default logger of the framework.
     *
     * @return Log
     */
    public function logger()
    {
        if (!$this->logger) {
            $dir = $this->app->path('log_root_dir').'menus/'.date('Y-m-d');
            $dir = Os::toPathStyle($dir);

            $exploded = explode($this->app->menuNamespaceDelimiter(), $this->name);

            $menuName = Str::pascalCase(array_pop($exploded));
            $menuRelativePath = $exploded ? implode(Os::slash(), $exploded) : '';
            $dir = !$menuRelativePath ?: $dir.'/'.$menuRelativePath;

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $file = $dir.'/'.$menuName.'.log';
            $cache = $dir.'/.count';
            $this->logger = new Log($file, $cache);
        }

        return $this->logger;
    }

    /**
     * The name of this menu.
     *
     * @return string
     */
    public function menuName()
    {
        return $this->name;
    }

    /**
     * The name of this menu.
     * Alias for `menuName`
     *
     * @return string
     */
    public function currentMenuName()
    {
        return $this->menuName();
    }

    /**
     * Application ID configured in the app.php config file.
     *
     * @return string
     */
    public function id()
    {
        return $this->app->id();
    }
    
    /**
     * Error on the current menu.
     *
     * @return string
     */
    public function error()
    {
        return $this->app->error();
    }

    /**
     * Set the error to be displayed on the user's screen.
     *
     * The error will overwrite any previously defined error (either by the framework or by the developer)
     *
     * @param  string $error
     * @return void
     */
    public function setError($error = '')
    {
        $error .= $this->app->error() ? "\n" : '';
        $this->app->setError($error);

        return $this;
    }

    /**
     * Add an error message to error stack, to be displayed on the user's screen.
     *
     * @param  string $error
     * @return void
     */
    public function addError($error = '')
    {
        $error = trim($this->app->error()."\n".$error);
        $this->app->setError($error);

        return $this;
    }

    public function setApp(Kernel $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get the raw response of the user for this menu.
     *
     * @return Response
     */
    public function response()
    {
        return $this->app->response();
    }

    /**
     * Get the raw response of the user for this menu.
     *
     * @return string
     */
    public function userResponse()
    {
        return $this->app->userResponse();
    }

    /**
     * Get the raw response of the user for this menu.
     *
     * @return string
     */
    public function userTrueResponse()
    {
        return $this->userResponse();
    }

    /**
     * Get the formatted response of the user for this menu.
     *
     * If the response has not been formatted (meaning if the response has not
     * been modified in the save_as attribute or in the saveAs method
     * of the menu entity, the raw response sent by the user is returned)
     *
     * @return mixed
     */
    public function userSavedResponse()
    {
        return $this->app->userSavedResponse();
    }

    public function isDevEnv()
    {
        return $this->app->isDevEnv();
    }

    public function isProdEnv()
    {
        return $this->app->isProdEnv();
    }
    
    public function isUssdChannel()
    {
        return $this->app->isUssdChannel();
    }

    public function network()
    {
        return $this->app->network();
    }

    public function channel()
    {
        return $this->app->channel();
    }

    public function sessionId()
    {
        return $this->app->sessionId();
    }

    public function mustNotTimeout()
    {
        return $this->app->session()->mustNotTimeout();
    }

    /**
     * Return the user previous responses.
     *
     * @param  string               $menuName The name of the menu response to retrieve
     * @param  string               $default  The default to pass when no response has been found for the menu provided
     * @return UserResponse|mixed
     */
    public function previousResponses(...$args)
    {
        return $this->app->previousResponses(...$args);
    }

    /**
     * Returns the next menu name or null if it has not yet been retieved by
     * the framework.
     *
     * The next menu name is retrieved from the menu flow in the menus.php file,
     * the actions or the default next menu parameter and is defined only when
     * the user's response has been successfully validated.
     * This means this will give the expected result (the proper menu name
     * based on the user's response) in all the methods that run before the
     * user response is validated (after, onMoveToNextMenu), but will be the
     * same as the `menuName` in all the methods that run before the user sends
     * a response or before the response is validated: (before, message,
     * actions, validate, saveAs)
     *
     * @return string
     */
    public function nextMenuName()
    {
        return $this->app->nextMenuName();
    }

    /**
     * Returns the previous menu name.
     *
     * @throws \RuntimeException If nothing is in the history
     * @return string
     */
    public function previousMenuName()
    {
        $length = count($this->historyBag());

        if (!$length) {
            throw new \RuntimeException('Cannot get any previous menu. The Menu history bag is empty.');
        }

        return $this->historyBag()[$length - 1];
    }

    /**
     * Returns the menu history bag.
     *
     * @return array
     */
    public function historyBag()
    {
        return $this->app->historyBag();
    }

    public function db($name = '')
    {
        return $this->app->db($name);
    }

    public function hasResumeFromLastSessionOnThisMenu()
    {
        return $this->app->hasResumeFromLastSession();
    }

    public function hasResumeFromLastSession()
    {
        return $this->app->hasResumeFromLastSession();
    }

    // public function __call($method, $args)
    // {
    //     if (method_exists($this->app, $method)) {
    //         return call_user_func([$this->app, $method], ...$args);
    //     }

    //     throw new \BadMethodCallException('Undefined method `'.$method.'` in class '.get_class($this));
    // }

    // ArrayAccess Interface
    public function offsetExists($offset)
    {
        $method = Str::camelCase($offset);

        return in_array($method, MENU_HOOKS, true) && method_exists($this, $method);
    }

    public function offsetGet($offset)
    {
        $method = Str::camelCase($offset);

        $args = in_array($method, RECEIVE_USER_RESPONSE, true) ? [
            $this->userResponse(),
            $this->previousResponses(),
        ] : [$this->previousResponses()];

        return call_user_func([$this, $method], ...$args);
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Cannot set '.$offset.'. Operation not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Cannot unset '.$offset.'. Operation not allowed.');
    }
}
