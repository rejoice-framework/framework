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

require_once __DIR__ . '/../../constants.php';

use Prinx\Notify\Log;
use Prinx\Os;
use Prinx\Rejoice\Menu\Menus;
use Prinx\Rejoice\Utils\SmsService;
use Prinx\Str;
use Prinx\Utils\HTTP;
use Prinx\Utils\URL;

/**
 * Main Library. Handle the request and return a response.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Kernel
{

    protected $appName = '';

    /**
     * Contains all the defined databases connetions
     *
     * @var \PDO[]
     */
    protected $appDBs = [];

    /**
     * @var PathConfig
     */
    protected $path;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Instance of the USSD session
     *
     * @var \Prinx\Rejoice\Session\Session
     */
    protected $session;

    /**
     * HTTP request wrapper instance
     *
     * @var Request
     */
    protected $request;

    /**
     * Class holding the response to send to the user
     *
     * @var Response
     */
    protected $response;

    /**
     * The request validator instance
     *
     * @var RequestValidator
     */
    protected $validator;

    /**
     * Class holding the menus and all the on-the-fly generated menu actions
     *
     * @var Menus
     */
    protected $menus;

    /**
     * @var Log
     */
    protected $logger;

    /**
     * @var \Prinx\Rejoice\Menu\BaseMenu|null
     */
    protected $currentMenuEntity = null;

    /**
     * @var \Prinx\Rejoice\Menu\BaseMenu|null
     */
    protected $nextMenuEntity = null;

    /**
     * Custom request type
     *
     * The custom request type help define a request type for handling restart
     * from last session and other requests.
     *
     * @var string
     */
    protected $customUssdRequestType;

    /**
     * @var string
     */
    protected $menuInProcess = 'none';

    /**
     * @var string
     */
    protected $nextMenuName = '';

    /**
     * True if the current menu is splitted
     *
     * The current menu is automatically splitted when the framework detects
     * that it will overflows. An automatic pagination is then performed.
     *
     * Do not rely on this pagination if the data will be a lot. Use rather the
     * more sophisticated Paginator trait to handle the pagination if the data
     * is a lot of if the amount of data that will be rendered is not known
     *
     * @var boolean
     */
    protected $currentMenuSplitted = false;

    /**
     * If the current menu is splitted, returns the index of the current
     * displayed chunk of the whole string to display
     *
     * @var integer
     */
    protected $currentMenuSplitIndex = 0;

    /**
     * True if the currentmenuSplitIndex is 0, meaning if the current displayed
     * page is the first menu chunk to display.
     *
     * This is mainly used to handle the display of the back option
     * The back option must not appear on the first menu chunk
     *
     * @var boolean
     */
    protected $currentMenuSplitStart = false;

    /**
     * True if the current displayed page is the last menu chunk to display
     *
     * This is mainly used to handle the display of the show more option.
     * The show more option must not appear on the last menu chunk
     *
     * @var boolean
     */
    protected $currentMenuSplitEnd = false;

    /**
     * True if the current menu has a back option
     *
     * This helps to properly handle the paginate back option when the menu is
     * splitted. If the menu does not contain a back option, a back option is
     * automatically added on the last screen of the paginated menu. But it's
     * important to know that the back option will take the user to the
     * previous menu only if the previous menu is the first chunk (if
     * currentMenuSplitStart === true)
     *
     * @var boolean
     */
    protected $currentMenuHasBackAction = false;

    /**
     * The current user response validation error
     *
     * @var string
     */
    protected $error = '';

    /**
     * This does not content the sms the user may send on the fly directly by
     * calling the sending sms function. It is rather used when the same menu
     * string message has to be sent to the user as SMS. Sometimes we need to
     * append to the string we are sending back to the user, some other
     * information like 'Press cancel to finish', information we do not want to
     * send via SMS. So as soon as the menu string has been generated, if
     * sending sms has been activated, we save it inside the sms before we
     * append anything else to the menu string.
     *
     * @var string
     */
    protected $sms = '';

    /**
     * True if the response has already been sent to the user but the script is
     * continuing, In other terms, this is true if the developer calls by
     * themselves, one of the methods `respond`, `respondAndContinue`,
     * `softEnd`. If this is true, the framework will terminate the script as
     * soon as the `before` method  of the menu entity has been called (the
     * menu entity inside which the menthod was called).
     *
     * @var boolean
     */
    protected $responseAlreadySentToUser = false;

    /**
     * True if the application databases have been loaded
     *
     * @var boolean
     */
    protected $appDbLoaded = false;

    /**
     * Set to true if the user has gone back to the previous menu (using the
     * __back option)
     *
     * @var boolean
     */
    protected $hasComeBack = false;

    /**
     * Set to true if the user has resumed from a previous session
     *
     * @var boolean
     */
    protected $hasResumeFromLastSession = false;

    protected $menuNamespaceDelimiter = '::';

    /**
     * The response of the user after applying the save_as parameter and/or the saveAs menu entity method
     *
     * @var mixed
     */
    protected $userSavedResponse = null;

    public function __construct($appName = '')
    {
        $this->appName = $appName;
        $this->path = new PathConfig;
        $this->logger = new Log(
            $this->path('app_default_log_file'),
            $this->path('app_default_cache_file')
        );
        $this->config = new Config([
            $this->path('app_config_dir'),
            $this->path('default_config_dir'),
        ]);
        $this->request = new Request($this);
        $this->response = new Response($this);
        $this->validator = new RequestValidator($this);
    }

    /**
     * Run the USSD application
     *
     * Exit the application when an error occurs.
     *
     * @return void
     */
    public function run()
    {
        ob_start();
        try {
            $this->validateRequest();
            $this->startSession();
            $this->loadMenus();
            $this->handleUserRequest();

        } catch (\Throwable $th) {
            $message = $this->formatExceptionMessage($th);
            $this->logger->critical($message);
            $this->fail($message);
        }
    }

    public function formatExceptionMessage($exception)
    {
        $message = '<strong>' . $exception->getMessage() . '</strong> in file ' . $exception->getFile() . ' on line ' . $exception->getLine();
        $message .= "<br><br>Processing menu: " . $this->menuInProcess;

        if (class_exists($this->menuEntityClass($this->menuInProcess))) {
            $menuPath = $this->path('app_menu_class_dir');

            $appNamespace = $this->createAppNamespace();
            $menuPath .= $appNamespace ? $appNamespace . '/' : '';
            $menuPath .= $this->menuEntityRelativePath($this->menuInProcess) . '.php';

            $message .= ' in file ' . Os::toPathStyle($menuPath);
        }

        $message .= '<br><br><strong>Stack trace:</strong><br>' . preg_replace('/(#[0-9]+)/', '<br><strong style="background:grey;padding:5px;">$1</strong>', $exception->getTraceAsString());

        return $message;
    }

    public function validateRequest()
    {
        $this->validator->validate();
    }

    /**
     * Get a configuration variable from the config
     *
     * Returns the config object instance if no parameter passed
     *
     * @param string $key
     * @param mixed $default The default to return if the configuration is not found
     * @param boolean $silent If true, will shutdown the exception throwing if configuration variable not found and no default was passed.
     * @return Config|mixed
     * @throws \RuntimeException
     */
    public function config($key = null, $default = null)
    {
        $args = func_get_args();

        return $this->config->get(...$args);
    }

    public function startSession()
    {
        $sessionConfigPath = $this->path('app_session_config_file');
        $sessionDriver = (require $sessionConfigPath)['driver'];
        $session = DEFAULT_NAMESPACE . 'Session\\' . ucfirst($sessionDriver) . 'Session';
        $this->session = new $session($this);
    }

    public function loadMenus()
    {
        $this->menus = new Menus($this);
    }

    public function isFirstRequest()
    {
        return $this->ussdRequestType() === APP_REQUEST_INIT;
    }

    public function forceRestart()
    {
        $this->setCustomUssdRequestType(APP_REQUEST_INIT);
    }

    public function attemptsToCallSubMenuDirectly()
    {
        return (!$this->isFirstRequest() &&
            $this->session->isNew());
    }

    public function doesNotAllowDirectSubMenuCall()
    {
        return !$this->config('app.allow_direct_sub_menu_call');
    }

    protected function handleUserRequest()
    {
        if ($this->isFirstRequest() && $this->session->isPrevious()) {
            // var_dump($this->session->data());
            $this->prepareToLaunchFromPreviousSession();
        } elseif (
            $this->attemptsToCallSubMenuDirectly() &&
            $this->doesNotAllowDirectSubMenuCall()
        ) {
            $this->forceRestart();
        }

        $this->menuInProcess = $this->currentMenuName();

        switch ($this->ussdRequestType()) {
            case APP_REQUEST_INIT:
                $this->runWelcomeState();
                break;

            case APP_REQUEST_USER_SENT_RESPONSE:
                if ($this->hasComeBackAfterNoTimeoutFinalResponse()) {
                    $this->allowTimeout();
                    $this->response->hardEnd();
                } elseif ($this->ussdHasSwitchedToRemote()) {
                    $this->processFromRemoteUssd();
                } else {
                    $this->processResponse();
                }

                break;

            case APP_REQUEST_ASK_USER_BEFORE_RELOAD_LAST_SESSION:
                $this->runAskUserBeforeReloadLastSessionState();
                break;

            case APP_REQUEST_RELOAD_LAST_SESSION_DIRECTLY:
                $this->runLastSessionState();
                break;

            case APP_REQUEST_CANCELLED:
                // $this->session->delete();
                $this->response->hardEnd('REQUEST CANCELLED');
                break;

            default:
                $this->response->hardEnd('UNKNOWN USSD SERVICE OPERATOR');
                break;
        }
    }

    public function prepareToLaunchFromPreviousSession()
    {
        if (
            $this->config('app.ask_user_before_reload_last_session') &&
            $this->session->metadata('current_menu_name', false) !== WELCOME_MENU_NAME
        ) {
            $this->setCustomUssdRequestType(APP_REQUEST_ASK_USER_BEFORE_RELOAD_LAST_SESSION);
        } else {
            $this->setCustomUssdRequestType(APP_REQUEST_RELOAD_LAST_SESSION_DIRECTLY);
        }
    }

    public function hasComeBackAfterNoTimeoutFinalResponse()
    {
        return $this->session->mustNotTimeout() && $this->session->isNew();
    }

    protected function runLastSessionState()
    {
        $this->hasResumeFromLastSession = true;
        $this->sessionSave('has_resume_from_last_session', true);
        $this->saveCurrentMenuName($this->historyBagPop());
        $this->runState($this->currentMenuName());
    }

    public function currentMenuName(): string
    {
        return $this->session->metadata('current_menu_name', 'welcome');
    }

    protected function saveCurrentMenuName($name): void
    {
        $this->session->setMetadata('current_menu_name', $name);
    }

    protected function runAskUserBeforeReloadLastSessionState()
    {
        $this->runState(ASK_USER_BEFORE_RELOAD_LAST_SESSION);
    }

    protected function processResponse()
    {
        $response = $this->userResponse();

        // Do not use empty() to check the user response. The expected response
        // can for e.g. be 0 (zero), which empty() sees like empty.
        if ('' === $response) {
            $this->runInvalidInputState($this->config('menu.empty_response_error'));
            return;
        }

        $currentMenu = $this->currentMenuName();

        $this->loadMenuEntity($currentMenu, 'currentMenuEntity');

        $responseExistsInMenuActions =
        isset($this->menus[$currentMenu][ACTIONS][$response][ITEM_ACTION]);

        $nextMenu = $this->menus->getNextMenuName(
            $response,
            $currentMenu,
            $responseExistsInMenuActions
        );

        $userError = false === $nextMenu;

        $mustValidateResponse = $this->mustValidateResponse(
            $userError,
            $responseExistsInMenuActions,
            $nextMenu,
            $currentMenu
        );

        if ($mustValidateResponse) {
            $responseValid = $this->validateUserResponse(
                $response,
                $currentMenu,
                $nextMenu
            );

            $userError = !$responseValid;
        }

        if ($userError) {
            if ($this->config('app.end_on_user_error')) {
                $this->response->hardEnd($this->config('menu.default_error_message'));
            } else {
                $this->runInvalidInputState();
            }

            return;
        }

        if (!$this->menus->menuStateExists($nextMenu)) {
            $class = $this->menuEntityClass($nextMenu);
            $this->response->addWarningInSimulator(
                'Neither the menu "' . $nextMenu . '" nor the class "' . $class . '" was found. '
            );

            $unhandledActionMessage = $this->config('menu.unhandled_action_message');
            if ($this->config('app.end_on_unhandled_action')) {
                $this->response->hardEnd($unhandledActionMessage);
            } else {
                $this->runInvalidInputState($unhandledActionMessage);
            }

            return;
        }

        if ($actionLater = $this->menus->getForcedFlowIfExists($currentMenu, $response)) {
            $this->menus->saveForcedFlow($actionLater);
        }

        $this->setNextMenuName($nextMenu);

        $this->saveUserResponse($response);

        $this->callAfterMenuHook($response);

        if (URL::isUrl($nextMenu)) {
            $this->callOnMoveToNextMenuHook($response);
            return $this->switchToRemoteUssd($nextMenu);
        }

        if ($this->isMovingToMenu($nextMenu)) {
            $this->callOnMoveToNextMenuHook($response);
        }

        return $this->runAppropriateState($nextMenu);
    }

    /**
     * Determines if the user is moving to a next menu (he is not comming back or he is not moving the next page of the same menu)
     *
     * @param string $nextMenu
     * @return boolean
     *
     * @todo Search a proper way of determining if moving to next menu
     */
    public function isMovingToMenu($nextMenu)
    {
        return (APP_END === $nextMenu || APP_WELCOME === $nextMenu ||
            !in_array($nextMenu, RESERVED_MENU_IDs));
    }

    /**
     * Determines if user's response for this particular menu must be validated.
     *
     * Not all response should be validated. Only responses that have not been
     * expressly defined by the developer will be validated. Hence, any reponse
     * for a menu that has a default_next_menu parameter must be validated (the
     * developer has to specify the validation rules or validate himself in the
     * `validate` method of the menu entity)
     *
     * @param string $currentMenu
     * @param string $userError
     * @param boolean $responseExistsInMenuActions The response has already been specified by the developer
     * @param string $nextMenu
     * @return boolean
     */
    public function mustValidateResponse($userError, $responseExistsInMenuActions, $nextMenu, $currentMenu)
    {
        return (
            /*(isset($this->menus[$currentMenu][DEFAULT_NEXT_MENU]) /* ||
            isset($this->menus[$currentMenu][ITEM_LATER])) &&*/!$userError &&
            !$responseExistsInMenuActions &&
            !in_array($nextMenu, RESERVED_MENU_IDs));
    }

    /**
     * Load the Menu Entity of a particular menu
     *
     * @param string $menuName
     * @param string $entityType ('currentMenuEntity'|'nextMenuEntity')
     * @return void
     */
    public function loadMenuEntity($menuName, $entityType)
    {
        $menuEntityClass = $this->menuEntityClass($menuName);

        if (!$this->$entityType && class_exists($menuEntityClass)) {
            $this->$entityType = new $menuEntityClass($menuName);
            $this->$entityType->setApp($this);
        }
    }

    /**
     * Call the proper method to run for the specific next menu
     *
     * @param string $nextMenu
     * @return void
     */
    protected function runAppropriateState($nextMenu)
    {
        switch ($nextMenu) {
            case APP_BACK:
                $this->runPreviousState();
                break;

            case APP_SPLITTED_MENU_NEXT:
                $this->runSameStateNextPage();
                break;

            case APP_END:
                $this->response->hardEnd();
                break;

            case APP_WELCOME:
                $this->runWelcomeState();
                break;

            case APP_SAME:
                $this->runSameState();
                break;

            case APP_CONTINUE_LAST_SESSION:
                $this->runLastSessionState();
                break;

            case APP_PAGINATE_FORWARD:
                $this->runPaginateForwardState();
                break;

            case APP_PAGINATE_BACK:
                $this->runPaginateBackState();
                break;

            default:
                $this->runState($nextMenu);
                break;
        }
    }

    /**
     * Switch the treatment of the ussd request to the remote ussd.
     *
     * From here any subsequent request will be forward to the remote ussd
     *
     * @param string $nextMenu
     * @return void
     */
    protected function switchToRemoteUssd($nextMenu)
    {
        $this->session->setMetadata('switched_ussd_endpoint', $nextMenu);
        $this->session->setMetadata('ussd_has_switched', true);
        $this->session->save();
        $this->setUssdRequestType(APP_REQUEST_INIT);

        return $this->processFromRemoteUssd($nextMenu);
    }

    /**
     * Save user's response in the session.
     *
     * If there is a `save_as` parameter in the menu flow, the value of the
     * `save_as` parameter is rather saved.
     *
     * If there is a `saveAs` method in the menu entity of the
     * particular menu, its result is rather saved.
     *
     * If both `save_as` and `saveAs` method are available, the
     * `save_as` parameter has the precedence
     *
     * @param string $userResponse
     * @return void
     */
    protected function saveUserResponse($userResponse)
    {
        $toSave = $userResponse;
        $name = $this->currentMenuName();
        $saveResponseMethod = MENU_ENTITY_SAVE_RESPONSE_AS;

        if (isset($this->menus[$name][ACTIONS][$userResponse][SAVE_RESPONSE_AS])) {
            $toSave = $this->menus[$name][ACTIONS][$userResponse][SAVE_RESPONSE_AS];
        } elseif (
            // !(
            //     $userResponseExistsInMenuActions &&
            //     in_array($nextMenuName, RESERVED_MENU_IDs, true)
            // ) &&
            $this->currentMenuEntity &&
            method_exists($this->currentMenuEntity, $saveResponseMethod)
        ) {
            $toSave = call_user_func(
                [$this->currentMenuEntity, $saveResponseMethod],
                $userResponse,
                $this->userPreviousResponses()
            );

            if (null === $toSave) {
                $class = get_class($this->currentMenuEntity);
                $this->response->addWarningInSimulator('The method `' . $saveResponseMethod .
                    '` in the class ' . $class . ' returns `NULL`.
                 That may means the method does not return anything or you are
                  deliberately returning NULL. <strong>NULL will be saved as
                  the user\'s response</strong>! Check that method (' .
                    $class . '::' . $saveResponseMethod . ') if you think it
                  must return something else.');
            }
        }

        $this->userSavedResponse = $toSave;
        $this->userPreviousResponsesAdd($toSave);
    }

    public function validateResponseFromRules($response, $rules)
    {
        $validation = UserResponseValidator::validate($response, $rules);

        if (!$validation->validated) {
            $this->error .= "\n" . $validation->error;
        }

        return $validation->validated;
    }

    public function validateFromMenuFlow($menuName, $response)
    {
        if (!isset($this->menus[$menuName][VALIDATE])) {
            return true;
        }

        $rules = $this->menus[$menuName][VALIDATE];

        return $this->validateResponseFromRules($response, $rules);
    }

    public function validateFromMenuEntity($nextMenuName, $response)
    {
        $validateMethod = MENU_ENTITY_VALIDATE_RESPONSE;

        if (
            !in_array($nextMenuName, RESERVED_MENU_IDs, true) &&
            $this->currentMenuEntity &&
            method_exists($this->currentMenuEntity, $validateMethod)
        ) {

            $validation = call_user_func(
                [$this->currentMenuEntity, $validateMethod],
                $response,
                $this->userPreviousResponses()
            );

            // The validation is suppose to be validation rules
            if (is_array($validation) || is_string($validation)) {
                return $this->validateResponseFromRules($response, $validation);
            }

            if (is_object($validation)) {
                if (!property_exists($validation, 'validated')) {
                    throw new \Exception('The object returned from the `' . $validateMethod . '` method does not seem to be a correct validation object. Please return a result of  `' . UserResponseValidator::class . '::validate($response, $rules)` or return an array of validation rules or a boolean.');
                }

                return $validation->validated;
            }

            if (!is_bool($validation)) {
                throw new \Exception('The method `' . $validateMethod . '` inside `' . get_class($this->currentMenuEntity) . '` class must return either an array of validation rules or a string of validation rules or a result of  <i>' . UserResponseValidator::class . '::validate($response, $rules)</i> or a boolean. But got ' . gettype($validation));
            }

            return $validation;
        }

        return true;
    }

    protected function validateUserResponse(
        $response,
        $menuName,
        $nextMenuName
    ) {
        $validated = $this->validateFromMenuFlow($menuName, $response);

        return $validated ? $this->validateFromMenuEntity($nextMenuName, $response) : $validated;
    }

    public function getDefaultNextMenuFromMenuEntity()
    {
        $defaultNextMethod = MENU_ENTITY_DEFAULT_NEXT_MENU;

        if (
            $this->currentMenuEntity &&
            method_exists($this->currentMenuEntity, $defaultNextMethod)
        ) {
            return call_user_func([$this->currentMenuEntity, $defaultNextMethod]);
        }

        return false;
    }

    protected function callAfterMenuHook($userResponse)
    {
        $afterMethod = MENU_ENTITY_AFTER;

        if (
            $this->currentMenuEntity &&
            method_exists($this->currentMenuEntity, $afterMethod)
        ) {
            call_user_func(
                [$this->currentMenuEntity, $afterMethod],
                $userResponse,
                $this->userPreviousResponses()
            );
        }
    }

    protected function callOnMoveToNextMenuHook($userResponse)
    {
        $onMoveToNextMenuMethod = MENU_ENTITY_ON_MOVE_TO_NEXT_MENU;

        if (
            $this->currentMenuEntity &&
            method_exists($this->currentMenuEntity, $onMoveToNextMenuMethod)
        ) {
            call_user_func(
                [$this->currentMenuEntity, $onMoveToNextMenuMethod],
                $userResponse,
                $this->userPreviousResponses()
            );
        }
    }

    protected function processFromRemoteUssd($endpoint = '')
    {
        $endpoint = $endpoint ?: $this->switchedUssdEndpoint();

        $response = HTTP::post($this->request->input(), $endpoint);

        if ($response['SUCCESS']) {
            $this->response->sendRemote($response['data']);
        } else {
            $this->response->sendRemote($response['error']);
        }
    }

    public function switchedUssdEndpoint()
    {
        return $this->session->metadata('switched_ussd_endpoint', '');
    }

    public function ussdHasSwitchedToRemote()
    {
        return $this->session->metadata('ussd_has_switched', false);
    }

    protected function runSameStateNextPage()
    {
        $this->userPreviousResponsesPop($this->currentMenuName());
        $this->runNextState(APP_SPLITTED_MENU_NEXT);
    }

    protected function getErrorIfExists($menuName)
    {
        if ($this->error() && $this->currentMenuName() === $menuName) {
            return $this->error();
        }

        return '';
    }

    protected function callOnBackHook()
    {
        $this->loadMenuEntity($this->currentMenuName(), 'currentMenuEntity');

        if (method_exists($this->currentMenuEntity, MENU_ENTITY_ON_BACK)) {
            call_user_func(
                [$this->currentMenuEntity, MENU_ENTITY_ON_BACK],
                $this->userPreviousResponses()
            );
        }
    }

    protected function messageFromMenuEntity($menuName)
    {
        $resultCallBefore = '';

        $callBefore = MENU_ENTITY_MESSAGE;

        if (
            $this->nextMenuEntity &&
            method_exists($this->nextMenuEntity, $callBefore)
        ) {
            $resultCallBefore = call_user_func(
                [$this->nextMenuEntity, $callBefore],
                $this->userPreviousResponses()
            );
        }

        if (isset($this->menus[$menuName][MSG])) {
            if (
                !is_string($resultCallBefore) &&
                !is_array($resultCallBefore)
            ) {
                throw new \RuntimeException("STRING OR ARRAY EXPECTED.\nThe function '" . $callBefore . "' in class '" . get_class($this->nextMenuEntity) . "' must return either a string or an associative array. If it returns a string, the string will be appended to the message of the menu. If it return an array, the library will parse the menu message and replace all words that are in the form :indexofthearray: by the value associated in the array. Check the documentation to learn more on how to use the '" . $callBefore . "' functions.");
            }
        } else {
            if (!is_string($resultCallBefore)) {
                throw new \RuntimeException("STRING EXPECTED.\nThe function '" . $callBefore . "' in class '" . get_class($this->nextMenuEntity) . "' must return a string if the menu itself does not have any message. Check the documentation to learn more on how to use the '" . $callBefore . "' functions.");
            }
        }

        return $resultCallBefore;
    }

    protected function actionsFromMenuEntity()
    {
        $actionHookResult = [];
        $actionHook = MENU_ENTITY_ACTIONS;

        if (
            $this->nextMenuEntity &&
            method_exists($this->nextMenuEntity, $actionHook)
        ) {
            $actionHookResult = call_user_func(
                [$this->nextMenuEntity, $actionHook],
                $this->userPreviousResponses()
            );
        }

        if (!is_array($actionHookResult)) {
            throw new \RuntimeException("ARRAY EXPECTED.\nThe method '" . $actionHook . "' in class '" . get_class($this->nextMenuEntity) . "' must return an associative array.");
        }

        return $actionHookResult;
    }

    protected function callBeforeHook()
    {
        $callBefore = MENU_ENTITY_BEFORE;

        if (
            $this->nextMenuEntity &&
            method_exists($this->nextMenuEntity, $callBefore)
        ) {
            call_user_func(
                [$this->nextMenuEntity, $callBefore],
                $this->userPreviousResponses()
            );
        }
    }

    protected function runState($nextMenuName)
    {
        $this->menuInProcess = $nextMenuName;

        $this->loadMenuEntity($nextMenuName, 'nextMenuEntity');

        $this->callBeforeHook();

        // If the developer has already sent the response (for example in the
        //`before` method), we terminate the script here, as the task of the
        // rest of this method is to format the response and send it to the user
        if ($this->responseAlreadySentToUser) {
            exit;
        }

        $message = $this->menuMessage($nextMenuName);
        $actions = $this->menuActions($nextMenuName);

        if ($this->menus->isLastPage($nextMenuName, $actions, $this->nextMenuEntity)) {
            $isUssdChannel = $this->isUssdChannel();

            // We save message for SMS before we add any "Cancel" message,
            // which is only let the user cancel the ussd prompt
            $this->sms = $message;

            if (
                $isUssdChannel &&
                $this->session->mustNotTimeout() &&
                $cancelMessage = $this->config('menu.cancel_message')
            ) {
                $sep = $this->app->config('menu.seperator_menu_string_and_cancel_message');
                $message .= $sep . $cancelMessage;
            }

            if (
                !$isUssdChannel ||
                ($isUssdChannel &&
                    !$this->menus->willOverflowWith($message))
            ) {
                $this->runLastState($message);
                return;
            }
        }

        $this->runNextState(
            $nextMenuName,
            $message,
            $actions,
            $this->currentMenuHasBackAction
        );
    }

    public function isUssdChannel()
    {
        return $this->channel() === 'USSD';
    }

    public function allowTimeout()
    {
        $this->config()->set('app.allow_timeout', true);
    }

    /**
     * Returns the message to be displayed for a particular menu
     *
     * The message is composed of 1. An error if there is one (eg: invalid
     * input error), 2. the message returned by the menu entity of this
     * particular menu and 3. the message specified in the menu flow.
     * Note that if the menu entity returns an array, we don't concatenate the
     * messages, we rather search in the message specified in the menu flow if
     * there is placeholders that match each index of the array and we replace
     * each placeholder by its value.
     *
     * @param string $menuName
     * @return string
     */
    protected function menuMessage($menuName)
    {
        $message = $this->getErrorIfExists($menuName);
        $menuEntityMessage = $this->messageFromMenuEntity($menuName);

        if (isset($this->menus[$menuName][MSG])) {
            $menuFlowMessage = $this->menus[$menuName][MSG];

            if (is_string($menuEntityMessage)) {
                if (empty($menuFlowMessage)) {
                    $menuFlowMessage = $menuEntityMessage;
                } else {
                    $menuFlowMessage = $menuEntityMessage ? $menuEntityMessage . "\n" . $menuFlowMessage : $menuFlowMessage;
                }
            } elseif (is_array($menuEntityMessage)) {
                foreach ($menuEntityMessage as $pattern_name => $value) {
                    $pattern = '/' . MENU_MSG_PLACEHOLDER . $pattern_name . MENU_MSG_PLACEHOLDER . '/';
                    $menuFlowMessage = preg_replace($pattern, $value, $menuFlowMessage);
                }
            }

            $message .= $message ? "\n" . $menuFlowMessage : $menuFlowMessage;
        } else {
            if (empty($message)) {
                $message = $menuEntityMessage;
            } else {
                $message = $menuEntityMessage ? $message . "\n" . $menuEntityMessage : $message;
            }
        }

        return $message;
    }

    /**
     * Return all the actions of the current menu.
     *
     * The actions come from two places: the actions defined in the menu flow
     * and the actions returned by the `actions` method in the menu entity of
     * the particular menu.
     * For each action, we save the extra parameters (the next menu to call,
     * the save_as parameter, etc.) in the session (persistMenuActions). and we
     * return only the messages for display purpose.
     *
     * @param string $menuName
     * @return array
     */
    protected function menuActions($menuName)
    {
        $this->currentMenuHasBackAction = false;

        $actionsFromMenuFlow = $this->menus[$menuName][ACTIONS] ?? [];
        $actionsFromMenuEntity = $this->actionsFromMenuEntity();

        // `array_merge` reorder the array, a behavior that we don't want here
        // The actions should be in the same order defined by the developer
        $actions = array_replace($actionsFromMenuFlow, $actionsFromMenuEntity);

        $toDisplay = [];
        $toSave = [];

        foreach ($actions as $actionTrigger => $value) {
            /*
             * Weird behavior of `array_search`!
             * It returns true if $actionTrigger is '0'
             * To be reviewed
             */
            if ('0' == $actionTrigger ||
                array_search($actionTrigger, RESERVED_MENU_ACTIONS) === false
            ) {
                $toDisplay[$actionTrigger] = $value[ITEM_MSG];
                $toSave[$actionTrigger] = $value;

                if (
                    !$this->currentMenuHasBackAction &&
                    isset($value[ITEM_ACTION]) && APP_BACK === $value[ITEM_ACTION] || APP_PAGINATE_BACK === $value[ITEM_ACTION]) {
                    $this->currentMenuHasBackAction = true;
                }
            }
        }

        $this->persistMenuActions($toSave, $menuName);
        return $toDisplay;
    }

    protected function persistMenuActions($actions, $menuName)
    {
        $this->setMenuActions($actions, $menuName);
    }

    protected function runWelcomeState()
    {
        if (!$this->menus->has(WELCOME_MENU_NAME)) {
            throw new \RuntimeException('No welcome menu defined. There must be at least one menu named `welcome` which will be the first displayed menu.');
        }

        $this->historyBagNew();
        $this->session->reset();
        $this->runState(WELCOME_MENU_NAME);
    }

    protected function runNextState(
        $nextMenuName,
        $message = '',
        $menuActions = [],
        $hasBackAction = false
    ) {
        $menuString = '';

        if (APP_SPLITTED_MENU_NEXT === $nextMenuName) {
            $menuString = $this->menus->getSplitMenuStringNext();
            $hasBackAction = $this->session
                ->metadata('currentMenuHasBackAction');
        } elseif (APP_SPLITTED_MENU_BACK === $nextMenuName) {
            $menuString = $this->menus->getSplitMenuStringBack();
            $hasBackAction = $this->session
                ->metadata('currentMenuHasBackAction');
        } else {
            $menuString = $this->menus->getMenuString(
                $menuActions,
                $message,
                $hasBackAction
            );
        }

        // In production mode send the response as soon as it is ready, before doing other processing
        if ($this->isProdEnv()) {
            $this->response->send($menuString);
        }

        if (APP_SPLITTED_MENU_NEXT !== $nextMenuName && APP_SPLITTED_MENU_BACK !== $nextMenuName) {
            if (
                $this->currentMenuName() &&
                $this->currentMenuName() !== WELCOME_MENU_NAME && ASK_USER_BEFORE_RELOAD_LAST_SESSION !== $nextMenuName &&
                !empty($this->historyBag()) && $this->previousMenuName() === $nextMenuName) {
                $this->historyBagPop();
            } elseif (
                $this->currentMenuName() && $this->currentMenuName() !== $nextMenuName &&
                $this->currentMenuName() !== ASK_USER_BEFORE_RELOAD_LAST_SESSION
            ) {
                $this->historyBagPush($this->currentMenuName());
            }

            $this->saveCurrentMenuName($nextMenuName);
        }

        $this->session->save();

        // In development mode sending the response is the last thing.
        // Helpful, if ever an error happens in the last processing (above) and
        // also to `mesure` how much time the overall processing lasts.
        if ($this->isDevEnv()) {
            $this->response->send($menuString);
        }
    }

    public function isDevEnv()
    {
        return in_array($this->config('app.environment'), DEV_ENV);
    }

    public function isProdEnv()
    {
        return !$this->isDevEnv();
    }

    protected function runLastState($message = '')
    {
        $message = trim($message);

        /*
         * In production, for timeout reason, push immediately the response
         * before doing any other thing, especially calling an API, like
         * sending a message, sending mobile money, etc., which may take time.
         */
        if ($this->isProdEnv()) {
            $this->response->softEnd($message);
        }

        if ($message && $this->config('app.always_send_sms_at_end')) {
            $sms = $this->sms ? $this->sms : $message;
            $this->sendSms($sms);
        }

        /*
         * In development, pushing the response to the user will rather be
         * the last thing, to be able to receive any ever error, warning or
         * info in the simulator.
         */
        if ($this->isDevEnv()) {
            $this->response->softEnd($message);
        }

        $this->session->hardReset();
    }

    public function fail($error)
    {
        // Get the session data before sending the response. As soon as the response is sent, the session is cleared.
        $sessionData = $this->session->data();

        $this->response->addErrorInSimulator($error);
        $this->response->softEnd(
            $this->config('menu.application_failed_message')
        );

        $log = "Error:\n" . $error . "\n\nUser session:\n" . json_encode($sessionData, JSON_PRETTY_PRINT);

        $this->logger->emergency($log);
        $this->sendSms("ERROR\n" . substr($error, 0, 140));
        exit;
    }

    protected function runPreviousState()
    {
        $this->hasComeBack = true;
        $this->userPreviousResponsesPop($this->currentMenuName());

        if (
            $this->session->hasMetadata('currentMenuSplitted') &&
            $this->session->metadata('currentMenuSplitted') &&
            $this->session->hasMetadata('currentMenuSplitIndex') &&
            $this->session->metadata('currentMenuSplitIndex') > 0
        ) {
            $this->runNextState(APP_SPLITTED_MENU_BACK);
        } else {
            $this->callOnBackHook();
            $previousMenuName = $this->previousMenuName();
            $this->userPreviousResponsesPop($previousMenuName);
            $this->runState($previousMenuName);
        }
    }

    protected function userPreviousResponsesPop($menuName)
    {
        if ($this->userPreviousResponses()) {
            if (
                isset($this->session->data['user_previous_responses'][$menuName]) &&
                is_array($this->session->data['user_previous_responses'][$menuName])
            ) {
                return array_pop($this->session->data['user_previous_responses'][$menuName]);
            }
        }

        return null;
    }

    protected function userPreviousResponsesAdd($response)
    {
        $id = $this->currentMenuName();

        if (
            !isset($this->userPreviousResponses()[$id]) ||
            !is_array($this->userPreviousResponses()[$id])
        ) {
            $this->session->data['user_previous_responses'][$id] = [];
        }

        $this->session->data['user_previous_responses'][$id][] = $response;
    }

    public function userPreviousResponses($menuName = null, $default = null)
    {
        if (!$this->session->hasMetadata('user_previous_responses')) {
            $this->session->setMetadata('user_previous_responses', []);
        }

        $previousSavedResponses = $this->session
            ->metadata('user_previous_responses');

        $responses = new UserResponse($previousSavedResponses);

        if ($menuName) {
            if (!$responses->has($menuName) && \func_num_args() > 1) {
                return $default;
            }

            return $responses->get($menuName);
        }

        return $responses;
    }

    protected function runSameState()
    {
        $this->userPreviousResponsesPop($this->currentMenuName());
        $this->runState($this->currentMenuName());
    }

    protected function callOnPaginateForwardHook()
    {
        $this->callOnPaginateHook(MENU_ENTITY_ON_PAGINATE_FORWARD);
    }

    protected function callOnPaginateBackHook()
    {
        $this->callOnPaginateHook(MENU_ENTITY_ON_PAGINATE_BACK);
    }

    protected function callOnPaginateHook($hook)
    {
        $this->loadMenuEntity($this->currentMenuName(), 'currentMenuEntity');

        if (method_exists($this->currentMenuEntity, $hook)) {
            call_user_func(
                [$this->currentMenuEntity, $hook],
                $this->userPreviousResponses()
            );
        }
    }

    protected function runPaginateForwardState()
    {
        $this->callOnPaginateForwardHook();
        $this->runSameState();
    }

    protected function runPaginateBackState()
    {
        $this->callOnPaginateBackHook();
        $this->runSameState();
    }

    protected function runInvalidInputState($error = '')
    {
        if (!$error) {
            $error = $this->error() ?: $this->config('menu.default_error_message');
        }

        $this->setError($error);
        $this->runState($this->currentMenuName());
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
     * @return array
     */
    public function insertMenuActions($actionBag, $replace = false, $menuName = '')
    {
        // To be reviewed: how to get the proper menu name, when not specified
        $menuName = $menuName ?: $this->nextMenuName();
        $menuName = $menuName ?: $this->currentMenuName();

        if (!$this->session->hasMetadata(CURRENT_MENU_ACTIONS)) {
            $this->session->setMetadata(CURRENT_MENU_ACTIONS, [ACTIONS => []]);
        }

        if ($replace) {
            $oldActions = $this->session->metadata(CURRENT_MENU_ACTIONS);
            $oldActions[ACTIONS] = $actionBag;
            $this->session->setMetadata(CURRENT_MENU_ACTIONS, $oldActions);
        } else {
            foreach ($actionBag as $actionTrigger => $actionParams) {
                $this->session->data[CURRENT_MENU_ACTIONS][ACTIONS][$actionTrigger] = $actionParams;
            }
        }

        return $this->menus->insertMenuActions($actionBag, $menuName, $replace);
    }

    /**
     * Empty, for this request, the actionBag of a particular menu
     *
     * @param string $menuName
     * @return void
     */
    public function emptyActionsOfMenu($menuName)
    {
        $this->session->removeMetadata(CURRENT_MENU_ACTIONS);
        $this->menus->emptyActionsOfMenu($menuName);
    }

    public function setMenuActions($actions, $menuName)
    {
        $this->emptyActionsOfMenu($menuName);
        $this->insertMenuActions($actions, $menuName);
    }

    protected function setNextMenuName($id)
    {
        $this->nextMenuName = $id;
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
        return $this->nextMenuName;
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
            throw new \RuntimeException("Can't get a previous menu. 'history_bag' is empty.");
        }

        return $this->historyBag()[$length - 1];
    }

    /**
     * Allows developer to save a value in the session
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function sessionSave($key, $value)
    {
        $this->session->set($key, $value);
    }

    /**
     * Allows developer to retrieve a previously saved value from the session.
     *
     * Returns the value associated to $key, if found. If the $key is not
     * in the session, it returns the $default passed. If no $default was
     * passed, it throws an exception.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     * @throws \Exception If the key is not found and no default has been passed
     */
    public function sessionGet($key, $default = null)
    {
        return $this->session($key, $default);
    }

    /**
     * Allow developer to check if the session contains an index.
     *
     * @param string $key
     * @return boolean
     */
    public function sessionHas($key)
    {
        return $this->session->has($key);
    }

    /**
     * Allow the developer to remove a key from the session
     *
     * @param string $key
     * @return boolean True if the key exists and has been removed
     */
    public function sessionRemove($key)
    {
        return $this->session->remove($key);
    }

    /**
     * Allows the developer to retrieve a value from the session.
     * This is identical to `sessionGet`
     * Returns the session instance if no parameter passed
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     * @throws \Exception If $key not found and no $default passed.
     */
    public function session($key = null, $default = null)
    {
        if (!$key) {
            return $this->session;
        }

        return $this->session->get($key, $default);
    }

    /**
     * Returns the menu history bag
     *
     * @return array
     */
    public function historyBag()
    {
        if (!$this->session->hasMetadata('history_bag')) {
            $this->historyBagNew();
        }

        return $this->session->metadata('history_bag');
    }

    public function historyBagNew()
    {
        $this->session->setMetadata('history_bag', []);
    }

    /**
     * Add a menu name to the history stack
     *
     * @param string $menuName
     * @return void
     */
    protected function historyBagPush($menuName)
    {
        $history = $this->historyBag();
        array_push($history, $menuName);
        $this->session->setMetadata('history_bag', $history);
    }

    /**
     * Remove a menu from the menu history stack.
     * The popped menu name is returned.
     *
     * @return string
     */
    protected function historyBagPop()
    {
        $history = $this->historyBag();
        $lastMenu = array_pop($history);
        $this->session->setMetadata('history_bag', $history);
        return $lastMenu;
    }

    /**
     * Load the application's configured databases.
     *
     * @return void
     */
    public function loadAppDBs()
    {
        $params = $this->config('database');

        if (!$params) {
            throw new \Exception('Invalid application database configuration');
        }

        $this->appDBs = Database::loadAppDBs($params);
        $this->appDbLoaded = true;
    }

    /**
     * Returns a specific configured database connection.
     * It returns the default connection if no connection name is provided
     *
     * @param string $connectionName
     * @return \PDO The PDO connection
     *
     * @throws \Exception If no it's unable to retrieve the configuration of the specified connection
     * @throws \Exception If no connectionNane passed and no default connection has been configured
     * @throws \Exception If connection to app database has not been activated in the application parameters (it is activated by default)
     */
    public function db($connectionName = '')
    {
        if ($this->config('app.connect_app_db')) {
            $connectionName = $connectionName ?: 'default';

            if (!$this->appDbLoaded) {
                $this->loadAppDBs();
            }

            if ('default' === $connectionName && !isset($this->appDBs['default'])) {
                throw new \Exception('No default database set! Kindly update your database configurations in "config/database.php". <br/> At least one database has to have the index "default" in the array return in "config/database.php". If not, you will need to specify the name of the database you want to load.');
            } elseif (!isset($this->appDBs[$connectionName])) {
                throw new \Exception('No database configuration set with the name "' . $connectionName . '" in "config/database.php"!');
            }

            return $this->appDBs[$connectionName];
        } else {
            throw new \Exception('Database not connected. Please set "connect_app_db" to boolean `true` in the "config/app.php" to enable connection to the database.');
        }
    }

    /**
     * Check if the user has gone back to arrived to the current menu
     *
     * @return boolean
     */
    public function hasComeBack()
    {
        return $this->hasComeBack;
    }

    public function createAppNamespace()
    {
        if (!$this->appName) {
            return '';
        }

        $namespace = Str::pascalCase($this->appName);

        return $namespace;
    }

    public function setResponseAlreadySentToUser($sent)
    {
        $this->responseAlreadySentToUser = $sent;
    }

    public function specificAppMenuEntitiesNamespace()
    {
        return $this->createAppNamespace();
    }

    public function menuNamespaceDelimiter()
    {
        return $this->menuNamespaceDelimiter;
    }

    public function menuEntityRelativePath($menuName, $delimiter = '\\')
    {
        $entityRelativePathChunks = explode($this->menuNamespaceDelimiter, $menuName);

        if (count($entityRelativePathChunks) <= 1) {
            return Str::pascalCase($menuName);
        } else {
            $entityRelativePathChunks = array_map(function ($element) {
                return Str::pascalCase($element);
            }, $entityRelativePathChunks);

            return join($delimiter, $entityRelativePathChunks);
        }
    }

    public function menuEntityClass($menuName)
    {
        $appNamespace = $this->specificAppMenuEntitiesNamespace();
        $appNamespace .= $appNamespace ? '\\' : '';

        $class = MENU_ENTITIES_NAMESPACE . $appNamespace . $this->menuEntityRelativePath($menuName);
        return $class;
    }

    public function currentMenuEntity()
    {
        return $this->currentMenuEntity;
    }

    public function nextMenuEntity()
    {
        return $this->nextMenuEntity;
    }

    public function id()
    {
        return $this->config('app.id');
    }

    public function error()
    {
        return $this->error;
    }

    public function msisdn()
    {
        return $this->request->input(
            $this->config('app.request_param_user_phone_number')
        );
    }

    public function network()
    {
        return $this->request->input(
            $this->config('app.request_param_user_phone_number')
        );
    }

    public function sessionId()
    {
        return $this->request->input($this->config('app.request_param_session_id'));
    }

    /**
     * Raw user response
     *
     * @return string
     */
    public function userResponse()
    {
        return $this->request->input(
            $this->config('app.request_param_user_response')
        );
    }

    public function channel()
    {
        return $this->request->input('channel');
    }

    public function ussdRequestType()
    {
        if (null !== $this->customUssdRequestType) {
            return $this->customUssdRequestType;
        }

        return $this->request->input(
            $this->config('app.request_param_request_type')
        );
    }

    public function setError(string $error = '')
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Retrieve the request input
     *
     * @param string $name
     * @return mixed
     */
    public function request($name = null)
    {
        if (!$name) {
            return $this->request;
        }

        return $this->request->input($name);
    }

    public function menus()
    {
        return $this->menus;
    }

    /**
     * Instance of the response to send back to the user
     *
     * @return Response
     */
    public function response()
    {
        return $this->response;
    }

    protected function setUssdRequestType($requestType)
    {
        $possibleTypes = [
            APP_REQUEST_INIT,
            APP_REQUEST_END,
            APP_REQUEST_CANCELLED,
            APP_REQUEST_ASK_USER_RESPONSE,
            APP_REQUEST_USER_SENT_RESPONSE,
        ];

        if (!in_array($requestType, $possibleTypes)) {
            $message = 'Trying to set a request type but the value provided "' . $requestType . '" is invalid.';
            throw new \Exception($message);
        }

        $this->request->forceInput(
            $this->config('app.request_param_request_type'),
            $requestType
        );

        return $this;
    }

    protected function setCustomUssdRequestType($requestType)
    {
        $this->customUssdRequestType = $requestType;
    }

    public function userSavedResponse()
    {
        return $this->userSavedResponse;
    }

    /**
     * Send SMS to a number
     *
     * If no msisdn is passed, the current msidn is used.
     * The sender name and endpoint can be configure in the env file or
     * directly in the config/app.php file
     *
     * @param string $message
     * @param string $msisdn
     * @param string $senderName
     * @param string $endpoint
     * @return void
     */
    public function sendSms($message, $msisdn = '', $senderName = '', $endpoint = '')
    {
        if (!$this->config('app.send_sms_enabled')) {
            return;
        }

        $smsService = new SmsService($this);
        return $smsService->send($message, $msisdn, $senderName, $endpoint);
    }

    /**
     * Return a path to a file or a folder
     *
     * @param string $key
     * @return string
     * @throws \RuntimeException
     */
    public function path($key = null)
    {
        if (!$key) {
            return $this->path;
        } elseif ($this->path->has($key)) {
            return $this->path->get($key);
        }

        throw new \RuntimeException("Key '$key' not found in the config");
    }

    public function logger()
    {
        return $this->logger;
    }

    public function hasResumeFromLastSessionOnThisMenu()
    {
        return $this->hasResumeFromLastSession;
    }

    public function hasResumeFromLastSession()
    {
        return $this->session('has_resume_from_last_session', null);
    }
}
