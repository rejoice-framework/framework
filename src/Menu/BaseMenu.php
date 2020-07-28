<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice\Menu;

use Prinx\Notify\Log;
use Prinx\Os;
use Prinx\Rejoice\Foundation\Kernel;
use Prinx\Str;

/**
 * Provides shortcuts to app methods and properties for the user App
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 *
 * @ method void before(UserResponse $userPreviousResponses)
 * Allows you to run a custom script before the menu is displayed to the user.
 * This method runs before every other method of the current menu entity
 *
 * @ method string|array message(UserResponse $userPreviousResponses)
 * Returns the message to display at top of the current menu screen. If it
 * returns an array, the indexes of the array will be assumed to be
 * placeholders inside the menu message defined in the menus.php file for this
 * particular menu.
 *
 * @ method array actions(UserResponse $userPreviousResponses)
 * Returns the actions of the current menu
 *
 * @ method UserResponseValidator|array|string|boolean validate(string $response, UserResponse $userPreviousResponses)
 * Validate the user's response. If it returns false, an invalid input error
 * will be sent to the user. You can customize the error by calling the
 * `addError` or `setError` method of the menu entity (Eg: $this->setError("The
 * age must be greater than 5"))
 *
 * @ method mixed saveAs(string $response, UserResponse $userPreviousResponses)
 * Allows to modify the user's response before saving it in the session.
 *
 * @ method void after(string $response, UserResponse $userPreviousResponses)
 * Allows you to run a custom script after the menu response has
 * been processed and the response of the user has passed the validation
 *
 * @ method mixed onMoveToNextMenu(string $response, UserResponse $userPreviousResponses)
 * Allows you to run a custom script after the menu response has
 * been processed and the user is moving the next screen. The back screen
 * (previous screen), the welcome screen, same screen, paginate screens (back
 * or forward) are not considered as next screen. Hence, this method will not
 * run for them. Instead use the after `method` if you want to consider them.
 *
 * @ method mixed onBack(UserResponse $userPreviousResponses)
 * Run when user goes back by using the __back magic menu
 *
 * @ method mixed onPaginateForward(UserResponse $userPreviousResponses)
 * Runs when when user moving forward in on a paginable menu
 *
 * @ method mixed onPaginateBack(UserResponse $userPreviousResponses)
 * Runs when when user moving back in on a paginable menu
 */
class BaseMenu/* implements \ArrayAccess */
{
    /**
     * The instance of the application
     *
     * @var Kernel
     */
    protected $app;

    /**
     * The name of this menu
     *
     * @var string
     */
    protected $name;

    /**
     * The instance of the logger on this menu
     *
     * @var Log
     */
    protected $logger;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string $msg
     * @return void
     */
    public function softEnd($msg)
    {
        if (
            $this->app->isUssdChannel() &&
            !$this->app->config('app.allow_timeout') &&
            $this->app->config('menu.cancel_message')
        ) {
            $sep = $this->app->config('menu.seperator_menu_string_and_cancel_message');
            $temp = $msg . $sep . $this->app->config('menu.cancel_message');

            $msg = $this->willOverflowWith($temp) ? $msg : $temp;
        }

        return $this->response()->softEnd($msg);
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string $msg
     * @return void
     */
    public function respond($msg)
    {
        $this->softEnd($msg);
    }

    /**
     * Sends the final response screen to the user but allows you to continue
     * the script.
     *
     * @param string $msg
     * @return void
     */
    public function respondAndContinue($msg)
    {
        $this->respond($msg);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string $msg
     * @return void
     */
    public function hardEnd($msg)
    {
        return $this->response()->hardEnd($msg);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string $msg
     * @return void
     */
    public function respondAndExit($msg)
    {
        $this->hardEnd($msg);
    }

    /**
     * Sends the final response screen to the user and automatically exits the
     * script.
     *
     * @param string $msg
     * @return void
     */
    public function terminate($msg)
    {
        $this->hardEnd($msg);
    }

    /**
     * Returns the phone number of the user
     *
     * @return string
     */
    public function tel()
    {
        return $this->msisdn();
    }

    /**
     * Log a message to the default log system
     * (storage/logs/{date}/{name_of_this_menu}.log)
     *
     * @param string|array $data
     * @return void
     * @throws \UnexpectedValueException If the level passed is unknown
     */
    public function log($data, $level = 'info')
    {
        if (!$this->config('app.log_enabled')) {
            return;
        }

        $this->logger()->log($level, $data);
    }

    /**
     * Returns the default logger of the framework
     *
     * @return Log
     */
    public function logger()
    {
        if (!$this->logger) {
            $dir = $this->app->path('log_root_dir') . 'menus/' . date('Y-m-d');
            $dir = Os::toPathStyle($dir);

            $exploded = explode($this->menuNamespaceDelimiter(), $this->name);

            $menuName = Str::pascalCase(array_pop($exploded));
            $menuRelativePath = $exploded ? join(Os::slash(), $exploded) : '';
            $dir = !$menuRelativePath ?: $dir . '/' . $menuRelativePath;

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $file = $dir . '/' . $menuName . '.log';
            $cache = $dir . '/.count';
            $this->logger = new Log($file, $cache);
        }

        return $this->logger;
    }

    /**
     * The name of this menu
     *
     * @return string
     */
    public function menuName()
    {
        return $this->name;
    }

    /**
     * The name of this menu
     *
     * @return string
     */
    public function currentMenuName()
    {
        return $this->menuName();
    }

    /**
     * Merge an action array with an actionBag
     *
     * @param array $actionBag
     * @param array $mergeWith
     * @return array
     */
    public function mergeAction($actionBag, $mergeWith)
    {
        return array_replace($actionBag, $mergeWith);
    }

    /**
     * Add a `go to main menu` action into the actions
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $option
     * @param string $display
     * @return array The modified action bag
     */
    public function insertMainMenuAction($option = '', $display = '')
    {
        return $this->insertMenuActions($this->mainMenuAction($option, $display));
    }

    /**
     * Return a `go to main menu` action bag
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array
     */
    public function mainMenuAction($trigger = '', $display = '')
    {
        $trigger = $trigger ?: $this->app->config('menu.welcome_action_trigger');
        $display = $display ?: $this->app->config('menu.welcome_action_display');

        return [
            $trigger => [
                ITEM_MSG => $display,
                ITEM_ACTION => APP_WELCOME,
            ],
        ];
    }

    /**
     * Insert a `go to previous menu` action into the actions
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array The modified action bag
     */
    public function insertBackAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->backAction($trigger, $display));
    }

    /**
     * Return an action bag containing a `go to previous menu` option, as an array
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array
     */
    public function backAction($trigger = '', $display = '')
    {
        $trigger = $trigger ?: $this->backTrigger();
        $trigger = $trigger ?: $this->app->config('menu.back_action_trigger');
        $display = $display ?: $this->app->config('menu.back_action_display');

        return [
            $trigger => [
                ITEM_MSG => $display,
                ITEM_ACTION => APP_BACK,
            ],
        ];
    }

    /**
     * Insert a `paginate back` action into the actions
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array The modified action bag
     */
    public function insertPaginateBackAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->paginateBackAction($trigger, $display));
    }

    /**
     * Return a `paginate back` action, as an array
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array
     */
    public function paginateBackAction($trigger = '', $display = '')
    {
        $trigger = $trigger ?: $this->backTrigger();
        $trigger = $trigger ?: $this->app->config('menu.paginate_back_trigger');
        $display = $display ?: $this->app->config('menu.paginate_back_display');

        return [
            $trigger => [
                ITEM_MSG => $display,
                ITEM_ACTION => APP_PAGINATE_BACK,
            ],
        ];
    }

    /**
     * Insert a `paginate forward` action into the actions
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array The modified action bag
     */
    public function insertPaginateForwardAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->paginateForwardAction($trigger, $display));
    }

    /**
     * Return a `paginate forward` action
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array
     */
    public function paginateForwardAction($trigger = '', $display = '')
    {
        $trigger = $trigger ?: $this->app->config('menu.paginate_forward_trigger');
        $display = $display ?: $this->app->config('menu.paginate_forward_display');

        return [
            $trigger => [
                ITEM_MSG => $display,
                ITEM_ACTION => APP_PAGINATE_FORWARD,
            ],
        ];
    }

    /**
     * Insert a `end USSD` action into the actions
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array The modified action bag
     */
    public function insertEndAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->endAction($trigger, $display));
    }

    /**
     * Return a `end USSD` action
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file)
     * Same for the display
     *
     * @param string $trigger
     * @param string $display
     * @return array
     */
    public function endAction($trigger = '', $display = '')
    {
        $trigger = $trigger ?: $this->app->config('menu.end_trigger');
        $display = $display ?: $this->app->config('menu.end_display');

        return [
            $trigger => [
                ITEM_MSG => $display,
                ITEM_ACTION => APP_END,
            ],
        ];
    }

    /**
     * Return an action bag after adding the back action to it
     *
     * @param array $actionBag
     * @return array
     */
    public function withBack($actionBag = [])
    {
        return $this->mergeAction($actionBag, $this->backAction());
    }

    /**
     * Defines the option the user will select to move back
     *
     * @return string
     */
    public function backTrigger()
    {
        return $this->backTrigger ?? '';
    }

    /**
     * Set the error to be displayed on the user's screen
     *
     * The error will overwrite any previously defined error (either by the framework or by the developer)
     *
     * @param string $error
     * @return void
     */
    public function setError($error = '')
    {
        $error .= $this->app->error() ? "\n" : '';
        $this->app->setError($error);
        return $this;
    }

    /**
     * Add an error message to error stack, to be displayed on the user's screen
     *
     * @param string $error
     * @return void
     */
    public function addError($error = '')
    {
        $error = trim($this->app->error() . "\n" . $error);
        $this->app->setError($error);
        return $this;
    }

    /**
     * Send SMS to a number
     *
     * If no phone number  (`$tel`) has been passed, the SMS will be sent to the current user (`$this->tel()`)
     *
     * If no `senderName` has been passed, the method will try to use any configured SMS_SENDER_NAME variable in the env file or the equivalent parameter in the config/app.php file (`sms_sender_name`). If this parameter is not found, the sms will just be discarded.
     *
     * If no `endpoint` has been passed, the method will try to use any configured SMS_ENDPOINT variable in the env file or the equivalent parameter in the config/app.php file (`sms_endpoint`). If this parameter is not found, the sms will just be discarded.
     *
     * @param string $sms The text to send
     * @param string $tel The phone number to send the SMS to.
     * @param string $senderName The name that will appear as the one who sent the SMS
     * @param string $endpoint The endpoint to send the SMS to.
     * @return void
     */
    public function sendSms($sms, $tel = '', $senderName = '', $endpoint = '')
    {
        $this->app->sendSms($sms, $tel, $senderName, $endpoint);
    }

    /**
     * Send SMS and exit the script
     *
     * @param string $sms
     * @param string $tel
     * @param string $sender
     * @param string $url
     * @return void
     */
    public function sendSmsAndExit($sms, $tel = '', $sender = '', $url = '')
    {
        $this->app->sendSms($sms, $tel, $sender, $url);
        exit;
    }

    public function setApp(Kernel $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Delete all the actions of a particular menu page ($menuName)
     *
     * @param string $menuName
     * @return void
     */
    public function emptyMenuActions($menuName = '')
    {
        $menuName = $menuName ?: $this->currentMenuName();
        $this->app->emptyActionsOfMenu($menuName);
    }

    /**
     * Initialise or re-initialise the menu actions to the $actions passed as argument
     *
     * If no menu name is passed, the current menu name is used.
     *
     * @param array $actions
     * @param string $menuName
     * @return void
     */
    public function setMenuActions($actions, $menuName = '')
    {
        $menuName = $menuName ?: $this->currentMenuName();
        $this->app->setMenuActions($actions, $menuName);
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

    public function isUssdChannel()
    {
        return $this->app->isUssdChannel();
    }

    public function mustNotTimeout()
    {
        return $this->app->session()->mustNotTimeout();
    }

    public function willOverflowWith($message)
    {
        return $this->app->menus()->willOverflowWith($message);
    }

    /**
     * Return the user previous responses
     *
     * @param string $menuName The name of the menu response to retrieve
     * @param string $default The default to pass when no response has been found for the menu provided
     *
     * @return UserResponse|mixed
     */
    public function userPreviousResponses(...$args)
    {
        return $this->app->userPreviousResponses(...$args);
    }

    /**
     * Add actions to the actions of a particualr menu.
     *
     * Any action that has the same index will be overwritten by the new action
     * in the actionBag. If the parameter replace is true, the old actions will
     * be rather completely replaced by the new actionBag.
     *
     * @param array $actionBag
     * @param boolean $replace
     * @param string $menuName
     * @return array The modified action bag
     */
    public function insertMenuActions($actionBag, $replace = false, $menuName = '')
    {
        $menuName = $menuName ?: $this->menuName();
        return $this->app->insertMenuActions($actionBag, $replace, $menuName);
    }

    /**
     * Empty, for this request, the actionBag of a particular menu
     *
     * @param string $menuName
     * @return void
     */
    public function emptyActionsOfMenu($menuName)
    {
        $this->app->emptyActionsOfMenu($menuName);
    }

    /**
     * Returns the next menu name or null if it has not yet been retieved by
     * the framework
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
     *
     * @return string
     */
    public function nextMenuName()
    {
        return $this->app->nextMenuName();
    }

    /**
     * Returns the previous menu name
     *
     * @return string
     * @throws \RuntimeException If nothing is in the history
     */
    public function previousMenuName()
    {
        $length = count($this->historyBag());

        if (!$length) {
            throw new \RuntimeException("Can't get a previous menu. 'back_history' is empty.");
        }

        return $this->historyBag()[$length - 1];
    }

    /**
     * Allows developer to save a value in the session
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function sessionSave($name, $value)
    {
        $this->session()->set($name, $value);
    }

    /**
     * Allow developer to retrieve a previously saved value from the session.
     *
     * Returns the value associated to $name, if found. If the key $name is not
     * in the session, it returns the $default passed. If no $default was
     * passed, it throws an exception.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function sessionGet($name, $default = null)
    {
        return $this->session($name, $default);
    }

    /**
     * Allow developer to check if the session contains an index.
     *
     * @param string $name
     * @return boolean
     */
    public function sessionHas($name)
    {
        return $this->session()->has($name);
    }

    /**
     * Allow the developer to remove a key from the session
     *
     * @param string $name
     * @return void
     */
    public function sessionRemove($name)
    {
        $this->session()->remove($name);
    }

    /**
     * Allow the developer to retrieve a value from the session.
     * This is identical to `sessionGet`
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     * @throws \RuntimeException If $key not found and no $default passed.
     */
    public function session($key = null, $default = null)
    {
        return $this->app->session($key, $default);
    }

    /**
     * Returns the menu history bag
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

    public function __call($method, $args)
    {
        if (method_exists($this->app, $method)) {
            return call_user_func([$this->app, $method], ...$args);
        }

        throw new \BadMethodCallException('Undefined method `' . $method . '` in class ' . get_class($this));
    }

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
            $this->userPreviousResponses(),
        ] : [$this->userPreviousResponses()];

        return call_user_func([$this, $method], ...$args);
    }

    public function offsetSet($offset, $value)
    {
        // Nothing to do
    }

    public function offsetUnset($offset)
    {
        // Nothing to do
    }
}
