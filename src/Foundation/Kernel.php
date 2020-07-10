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

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../dotenv/src/aliases_functions.php';
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../Session/FileSession.php';
require_once __DIR__ . '/../Session/DatabaseSession.php';
require_once __DIR__ . '/../Menu/Menus.php';
require_once __DIR__ . '/../Utils/SmsService.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/UserResponse.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/RequestValidator.php';
require_once __DIR__ . '/Response.php';

use Prinx\Rejoice\Foundation\Database;
use Prinx\Rejoice\Foundation\FrameworkConfig;
use Prinx\Rejoice\Foundation\Request;
use Prinx\Rejoice\Foundation\RequestValidator;
use Prinx\Rejoice\Foundation\Response;
use Prinx\Rejoice\Foundation\UserResponse;
use Prinx\Rejoice\Foundation\UserResponseValidator;
use Prinx\Rejoice\Menu\Menus;
use Prinx\Rejoice\Utils\Log;
use Prinx\Rejoice\Utils\SmsService;
use Prinx\Utils\HTTP;
use Prinx\Utils\Str;
use Prinx\Utils\URL;

/**
 * Main Library. Handle the request and return a response.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

class Kernel
{
    /**
     * App default name
     *
     * @var string
     */
    protected $appName = 'default';

    /**
     * Contains all the defined databases connetions
     *
     * @var string
     */
    protected $appDBs = [];

    /**
     * Applications parameters retrieved from the config/app.php
     *
     * @var array
     */
    protected $params = [];

    /**
     * Instance of the configurations of the framework itself
     *
     * @var FrameworkConfig
     */
    protected $frameworkConfig;
    /**
     * Instance of the USSD session
     *
     * @var Session
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
     * Default logger instance
     *
     * @var Log
     */
    protected $logger;

    /**
     * Menu entity of the current menu
     *
     * @var Menu|null
     */
    protected $currentMenuEntity = null;

    /**
     * Menu entity of the next menu
     *
     * @var Menu|null
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
     * Next menu name
     *
     * Based on the response of the user. This is generated
     * from the menu flow or the default next menu parameter or method
     *
     * @var string|null
     */
    protected $nextMenuName = null;

    /**
     * True if the current menu is splitted
     *
     * The current menu is automatically splitted when the framework detects
     * that it will overflows. An automatic pagination is then performed.
     *
     * Do not rely on this pagination if the data will be a lot. Use rather the
     * more sophisticated Paginator trait to handle the pagination if the data
     * is a lot the amount of data that will be rendered is not known
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
     * Maximum numbers of characters that can be displayed on a USSD screen
     *
     * @var integer
     */
    protected $maxUssdPageContent = 147;

    /**
     * Maximum new lines that can be displayed on a USSD screen
     *
     * @var integer
     */
    protected $maxUssdPageLines = 10;

    /**
     * The current user response validation error
     *
     * @var string
     */
    protected $error = '';

    /**
     * The SMS to send.
     *
     * This allows to differientiate the SMS to send from the string to send
     * back to the user. Sometimes we need to append to the the
     * string we are sending back to the user, some other information like
     * 'Press cancel to finish', information we do not want to send via SMS. So
     * as soon as the menu string has been generated, if sending sms has been
     * activated, we save it inside the sms before we append anything else to
     * the menu string.
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
     * menu entity inside which the `respond` menthod was called).
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

    /**
     * The response of the user after applying the save_as parameter and/or the saveAs menu entity method
     *
     * @var mixed
     */
    protected $userSavedResponse = null;

    public function __construct($appName)
    {
        $this->appName = $appName;
        $this->logger = new Log;
        $this->frameworkConfig = new FrameworkConfig;
        $this->request = new Request;
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
            $this->loadAppParams();
            $this->validateRequest();
            $this->startSession();
            $this->loadMenus();
            $this->handleUserRequest();
        } catch (\Throwable $th) {
            $message = $th->getMessage() . "\nin file " . $th->getFile();
            $message .= ":" . $th->getLine();
            $this->logger->critical($message);
            exit($message);
        }
    }

    public function validateRequest()
    {
        $this->validator->validate();
    }

    public function loadAppParams()
    {
        $params = require_once $this->frameworkConfig('app_config_path');
        $defaultParams = require_once __DIR__ . '/../params.php';
        $this->params = array_replace($defaultParams, $params);
    }

    public function startSession()
    {
        $sessionConfigPath = $this->frameworkConfig('session_config_path');
        $sessionDriver = (require $sessionConfigPath)['driver'];
        $session = $this->frameworkConfig('default_namespace') . 'Session\\' . ucfirst($sessionDriver) . 'Session';
        $this->session = new $session($this);
    }

    public function loadMenus()
    {
        $this->menus = new Menus($this);
    }

    protected function handleUserRequest()
    {
        if (
            $this->ussdRequestType() === APP_REQUEST_INIT &&
            $this->session->isPrevious()
        ) {
            $this->prepareToLaunchFromPreviousSessionState();
        }

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

    public function prepareToLaunchFromPreviousSessionState()
    {
        if (
            $this->params('ask_user_before_reload_last_session') &&
            !empty($this->session->metadata()) &&
            $this->session->metadata('current_menu_name') !== WELCOME_MENU_NAME
        ) {
            $this->setCustomUssdRequestType(APP_REQUEST_ASK_USER_BEFORE_RELOAD_LAST_SESSION);
        } else {
            $this->setCustomUssdRequestType(APP_REQUEST_RELOAD_LAST_SESSION_DIRECTLY);
        }
    }

    public function hasComeBackAfterNoTimeoutFinalResponse()
    {
        return $this->mustNotTimeout() && empty($this->session->metadata());
    }

    protected function runLastSessionState()
    {
        $this->hasResumeFromLastSession = true;
        $this->sessionSave('has_resume_from_last_session', true);
        $this->setCurrentMenuName($this->backHistoryPop());
        $this->runState($this->currentMenuName());
    }

    public function currentMenuName()
    {
        return $this->session->metadata('current_menu_name', 'welcome');
    }

    protected function setCurrentMenuName($name)
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

        /*
         * Do not use empty() to check the user response. The expected response
         * can for e.g. be 0 (zero), which empty() sees like empty.
         */
        if ($response === '') {
            $this->runInvalidInputState('Empty response not allowed');
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

        $userError = $nextMenu === false;

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
            if ($this->params('end_on_user_error')) {
                $this->response->hardEnd($this->default_error_msg);
            } else {
                $this->runInvalidInputState();
            }

            return;
        }

        if (!$this->menus->menuStateExists($nextMenu)) {
            $this->response->addWarningInSimulator(
                'The next menu `' . $nextMenu . '` cannot be found.'
            );

            if ($this->params('end_on_unhandled_action')) {
                $this->response->hardEnd('Action not handled.');
            } else {
                $this->runInvalidInputState('Action not handled.');
            }

            return;
        }

        if ($actionLater = $this->menus->getForcedFlowIfExists($currentMenu, $response)) {
            $this->menus->saveForcedFlow($actionLater);
        }

        $this->setNextMenuName($nextMenu);

        $this->saveUserResponse($response);

        $this->callAfterMenuHook($response);

        if ($this->isMovingToMenu($nextMenu)) {
            $this->callOnMoveToNextMenuHook($response);
        }

        if (URL::isUrl($nextMenu)) {
            $this->callOnMoveToNextMenuHook($response);
            return $this->switchToRemoteUssd($nextMenu);
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
        return ($nextMenu === APP_END ||
            $nextMenu === APP_WELCOME ||
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
            /*(isset($this->menus[$currentMenu][DEFAULT_MENU_ACTION]) /* ||
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
                // $this->setCurrentMenuName($menu = $this->backHistoryPop());
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
                $toSave,
                $this->userPreviousResponses()
            );

            if ($toSave === null) {
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
        if ($this->error() && $menuName === $this->currentMenuName()) {
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
        $this->loadMenuEntity($nextMenuName, 'nextMenuEntity');

        $this->callBeforeHook();

        // The response can be sent inside the `before` hook.
        // If so, we terminate the script here, as the task of the rest of
        // this method is to format the response and send it to the user
        if ($this->responseAlreadySentToUser) {
            exit;
        }

        $message = $this->menuMessage($nextMenuName);
        $actions = $this->menuActions($nextMenuName);

        if ($this->isLastMenuPage($actions)) {
            $isUssdChannel = $this->isUssdChannel();

            /* We save message for SMS before we add any "Cancel" message,
             * which is only let the user cancel the ussd prompt
             */
            $this->sms = $message;

            if (
                $isUssdChannel &&
                $this->mustNotTimeout() &&
                $this->params('cancel_msg')
            ) {
                $message .= "\n\n" . $this->params('cancel_msg');
            }

            if (
                !$isUssdChannel ||
                ($isUssdChannel &&
                    !$this->willOverflow($message))
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

    public function mustNotTimeout()
    {
        return $this->params('allow_timeout') === false;
    }

    public function allowTimeout()
    {
        $this->setParam('allow_timeout', true);
    }

    public function willOverflow($message)
    {
        return (strlen($message) > $this->maxUssdPageContent() ||
            count(explode("\n", $message)) > $this->maxUssdPageLines());
    }

    /**
     * Check if the menu will be the last to show to the user
     *
     * Any menu that does not have actions will be the last to show to the user
     * (no actions means the developer is no more waiting for any response, so
     * the user can no more input something)
     *
     * @param array $actions
     * @return boolean
     */
    protected function isLastMenuPage($actions)
    {
        return empty($actions);
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
            if (
                $actionTrigger == '0' ||
                array_search($actionTrigger, RESERVED_MENU_ACTIONS) === false
            ) {
                $toDisplay[$actionTrigger] = $value[ITEM_MSG];
                $toSave[$actionTrigger] = $value;

                if (
                    !$this->currentMenuHasBackAction &&
                    isset($value[ITEM_ACTION]) &&
                    $value[ITEM_ACTION] === APP_BACK ||
                    $value[ITEM_ACTION] === APP_PAGINATE_BACK
                ) {
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
            throw new \Exception('No welcome menu defined. There must be at least one menu named `welcome` which will be the first displayed menu.');
        }

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

        if ($nextMenuName === APP_SPLITTED_MENU_NEXT) {
            $menuString = $this->menus->getSplitMenuStringNext();
            $hasBackAction = $this->session
                ->metadata('currentMenuHasBackAction');
        } elseif ($nextMenuName === APP_SPLITTED_MENU_BACK) {
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

        if ($this->params('environment') !== DEV) {
            $this->response->send($menuString);
        }

        if (
            $nextMenuName !== APP_SPLITTED_MENU_NEXT &&
            $nextMenuName !== APP_SPLITTED_MENU_BACK
        ) {
            if (
                $this->currentMenuName() &&
                $this->currentMenuName() !== WELCOME_MENU_NAME &&
                $nextMenuName !== ASK_USER_BEFORE_RELOAD_LAST_SESSION &&
                !empty($this->backHistory()) &&
                $nextMenuName === $this->previousMenuName()
            ) {
                $this->backHistoryPop();
            } elseif (
                $this->currentMenuName() &&
                $nextMenuName !== $this->currentMenuName() &&
                $this->currentMenuName() !== ASK_USER_BEFORE_RELOAD_LAST_SESSION
            ) {
                $this->backHistoryPush($this->currentMenuName());
            }

            $this->setCurrentMenuName($nextMenuName);
        }

        $this->session->save();
        // In development mode send the response only after everything has been done
        if ($this->params('environment') === DEV) {
            $this->response->send($menuString);
        }
    }

    protected function runLastState($message = '')
    {
        $message = trim($message);

        /*
         * In production, for timeout reason, push immediately the response
         * before doing any other thing, especially calling an API, like
         * sending a message, which may take time.
         */
        if ($this->params('environment') !== DEV) {
            $this->response->softEnd($message);
        }

        if ($message && $this->params('always_send_sms_at_end')) {
            $sms = $this->sms ? $this->sms : $message;
            $this->sendSms($sms);
        }

        /*
         * In development, pushing the response to the user will rather be
         * the last thing, to be able to receive any ever error, warning or
         * info in the simulator.
         */
        if ($this->params('environment') === DEV) {
            $this->response->softEnd($message);
        }

        $this->session->hardReset();
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

        // var_dump($id);
        // var_dump($response);
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
            $error = $this->error() ?: $this->params('default_error_msg');
        }

        $this->setError($error);
        $this->runState($this->currentMenuName());
    }

    /**
     * The formatter is removing the public|protected when the method name
     * is 'exit'
     * TO BE REVIEWED.
     */
    function exit($message = '') {
        $this->response->hardEnd($message);
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
        $length = count($this->backHistory());

        if (!$length) {
            throw new \RuntimeException("Can't get a previous menu. 'back_history' is empty.");
        }

        return $this->backHistory()[$length - 1];
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
     * Allow the developer to retrieve a value from the session.
     * This is identical to `sessionGet`
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
    public function backHistory()
    {
        if (!$this->session->hasMetadata('back_history')) {
            $this->session->setMetadata('back_history', []);
        }

        return $this->session->metadata('back_history');
    }

    /**
     * Add a menu name to the history stack
     *
     * @param string $menuName
     * @return void
     */
    protected function backHistoryPush($menuName)
    {
        $history = $this->backHistory();
        array_push($history, $menuName);
        $this->session->setMetadata('back_history', $history);
    }

    /**
     * Remove a menu from the menu history stack.
     * The popped menu name is returned.
     *
     * @return string
     */
    protected function backHistoryPop()
    {
        $history = $this->backHistory();
        $lastMenu = array_pop($history);
        $this->session->setMetadata('back_history', $history);
        return $lastMenu;
    }

    /**
     * Load the application's configured databases.
     *
     * @return void
     */
    public function loadAppDBs()
    {
        $this->appDBs = Database::loadAppDBs();
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
        if ($this->params('connect_app_db')) {
            $connectionName = $connectionName ?: 'default';

            if (!$this->appDbLoaded) {
                $this->loadAppDBs();
            }

            if ($connectionName === 'default' && !isset($this->appDBs['default'])) {
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
     * Returns the maximum number of characters that can appear on one page of the screen of the targetted channel.
     *
     * For example, USSD screen will not support, in average, more than 147 characters.
     * This will be unlimited if the channel is whatsapp (or any other whatsapp-like channel)
     *
     * Will return -1 for unlimited characters
     *
     * @return int
     */
    public function maxUssdPageContent()
    {
        return $this->maxUssdPageContent;
    }

    /**
     * Returns the maximum number of lines (\n) that can appear on one page of the screen of the targetted channel.
     *
     * For example, USSD screen will not support, in average, more than 10 new lines.
     * This will be unlimited if the channel is whatsapp (or any other whatsapp-like channel)
     *
     * Will return -1 for unlimited lines
     *
     * @return int
     */
    public function maxUssdPageLines()
    {
        return $this->maxUssdPageLines;
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

    public function createAppNamespace($suffix = '')
    {
        // If no app name is provided, its means the menus or menu entities are
        // all in their respective root folders. No app namespace will be used
        if (!$this->appName) {
            return '';
        }

        $namespace = Str::pascalCase($this->appName);

        // // $pos = strpos(
        // //     $namespace,
        // //     $suffix,
        // //     strlen($namespace) - strlen($suffix)
        // // );

        // // $notAlreadySuffixed = $pos === -1 || $pos !== 0;
        // $notAlreadySuffixed = !Str::startsWith($namespace, $suffix);

        // if ($notAlreadySuffixed) {
        $namespace .= $suffix;
        // }

        return $namespace;
    }

    public function setResponseAlreadySentToUser($sent)
    {
        $this->responseAlreadySentToUser = $sent;
    }

    public function specificAppMenusNamespace()
    {
        return $this->createAppNamespace(MENUS_NAMESPACE_PREFIX);
    }

    public function specificAppMenuEntitiesNamespace()
    {
        return $this->createAppNamespace(MENU_ENTITIES_NAMESPACE_PREFIX);
    }

    public function menuEntityName($menuName)
    {
        return Str::pascalCase($menuName);
    }

    public function menuEntityClass($menuName)
    {
        $appNamespace = $this->specificAppMenuEntitiesNamespace();
        $appNamespace .= $appNamespace ? '\\' : '';

        $class = MENU_ENTITIES_NAMESPACE . $appNamespace . $this->menuEntityName($menuName);
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
        return $this->params('id');
    }

    public function setParam($name, $value)
    {
        return $this->params[$name] = $value;
    }

    public function error()
    {
        return $this->error;
    }

    public function msisdn()
    {
        return $this->request->input('msisdn');
    }

    public function network()
    {
        return $this->request->input('network');
    }

    public function sessionId()
    {
        return $this->request->input('sessionID');
    }

    /**
     * Raw user response
     *
     * @return string
     */
    public function userResponse()
    {
        return $this->request->input('ussdString');
    }

    public function channel()
    {
        return $this->request->input('channel');
    }

    public function ussdRequestType()
    {
        if ($this->customUssdRequestType !== null) {
            return $this->customUssdRequestType;
        }

        return $this->request->input('ussdServiceOp');
    }

    public function setError(string $error = '')
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Returns a request parameter
     *
     * @param string $name
     * @return mixed
     */
    public function params($name)
    {
        return $this->params[$name];
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

        $this->request->forceInput('ussdServiceOp', $requestType);

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
        if (!$this->params('send_sms_enabled')) {
            return;
        }

        $smsService = new SmsService($this);
        return $smsService->send($message, $msisdn, $senderName, $endpoint);
    }

    /**
     * Return a config parameter
     *
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function frameworkConfig($key = null)
    {
        if (!$key) {
            return $this->frameworkConfig;
        } elseif ($this->frameworkConfig->has($key)) {
            return $this->frameworkConfig->get($key);
        }

        throw new \Exception('Key `' . $key . '` not found in the config');
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
