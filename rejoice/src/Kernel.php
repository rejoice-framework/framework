<?php
/**
 * Main Library. Handle the request and return a response.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 * @license MIT
 */

namespace Prinx\Rejoice;

require_once realpath(__DIR__) . '/../../../autoload.php';

require_once realpath(__DIR__) . '/../../dotenv/src/aliases_functions.php';
require_once 'constants.php';
// require_once 'Utils.php';
require_once 'Validator.php';
require_once 'FileSession.php';
require_once 'DatabaseSession.php';
require_once 'Menus.php';
require_once 'Database.php';
require_once 'UserResponse.php';

include_once realpath(__DIR__) . '/../../../../app/Helpers/helpers.php';

use function Prinx\Dotenv\env;
use Prinx\Rejoice\Config;
use Prinx\Utils\Str;
use Prinx\Utils\URL;

class Kernel
{
    protected $app_name = 'default';
    protected $app_DBs = [];

    public $session_data = [];

    protected $session;

    protected $validator;

    protected $current_menu_entity = null;
    protected $next_menu_entity = null;

    protected $menus;

    protected $request_params = [
        'msisdn' => '',
        'ussdString' => '',
        'ussdServiceOp' => '',
        'network' => '',
        'channel' => 'USSD',
    ];

    protected $custom_ussd_request_type;

    protected $app_params = [
        'id' => '',
        'environment' => DEV,
        'back_action_thrower' => '0',
        'back_action_display' => 'Back',
        'splitted_menu_next_thrower' => '99',
        'splitted_menu_display' => 'More',
        'default_end_msg' => 'Thank you!',
        'end_on_user_error' => false,
        'end_on_unhandled_action' => false,
        'validate_shortcode' => false,
        'connect_app_db' => false,

        /*
         * Use by the Session instance to know if it must start a new
         * session or use the user previous session, if any.
         */
        'always_start_new_session' => true,

        /*
         * This property has no effect when "always_start_new_session"
         * is false
         */
        'ask_user_before_reload_last_session' => false,

        /*
         * Will send the final message as if we are requesting for
         * a response from the user.
         * This will be a workaround for long USSD flow where the session
         * likely to timeout, hence, the user is not be able to see the
         * final response.
         *
         * Setting this to true, the user will always get
         * the final response, but only it will be like we are asking them
         * a response. So it will be always better to add something like
         * "Press Cancel to end."
         */
        'allow_timeout' => true,
        'cancel_msg' => 'Press Cancel to end.',

        'always_send_sms_at_end' => false,
        'sms_sender_name' => '',
        'sms_endpoint' => '',
        'default_error_msg' => 'Invalid input',
    ];

    protected $next_menu_id = null;

    protected $error = '';

    protected $current_menu_splitted = false;
    protected $current_menu_split_index = 0;
    protected $current_menu_split_start = false;
    protected $current_menu_split_end = false;
    protected $current_menu_has_back_action = false;

    protected $max_ussd_page_content = 147;
    protected $max_ussd_page_lines = 10;

    protected $sms = '';

    protected $end_method_already_called = false;

    protected $warning_in_simulator = [];
    protected $info_in_simulator = [];

    protected $app_db_loaded = false;

    public function __construct()
    {
        $this->validator = Validator::class;
    }

    public function run($app_name = 'default')
    {
        $this->app_name = $app_name;
        $this->config = new Config;

        try {
            $this->validator::validateRequestParams($_POST);

            $this->hydrate($_POST);

            $this->validateShortcodeIfRequestInit();

            $session_driver = (require $this->config->get('session_config_path'))['driver'];
            $session = $this->config->get('default_namespace') . ucfirst($session_driver) . 'Session';

            $this->session = new $session($this);
            $this->session_data = $this->session->data();

            /*
            if ($this->app_params['connect_app_db']) {
            $this->app_DBs = Database::loadAppDBs($current_menu_entity);
            } */

            $this->menus = new Menus($this);

            if (
                $this->ussdRequestType() === APP_REQUEST_INIT &&
                $this->session->isPrevious()
            ) {
                $this->prepareToLaunchFromPreviousSessionState();
            }

            $this->handleUserRequest();
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            $message .= "\nin file " . $th->getFile();
            $message .= ":" . $th->getLine() . "\n\n";
            echo $message;
            $message = date("D, d m Y, H:i:s\n") . $message;
            logMessage($message);
        }
    }

    protected function hydrate($request_params)
    {
        $this->hydrateAppParams();
        $this->hydrateRequestParams($request_params);
    }

    protected function validateShortcodeIfRequestInit()
    {
        if (
            $this->ussdRequestType() === APP_REQUEST_INIT &&
            $this->app_params['validate_shortcode']
        ) {
            $shortcode_correct = $this->validator::validateShortcode(
                $this->userResponse(),
                env('SHORTCODE', null)
            );

            if (!$shortcode_correct) {
                $this->addWarningInSimulator(
                    'INVALID SHORTCODE <strong>' . $this->userResponse() .
                    '</strong><br/>Use the shortcode defined in the .env file.'
                );

                $this->hardEnd('INVALID SHORTCODE');
            }
        }
    }

    public function prepareToLaunchFromPreviousSessionState()
    {
        if (
            $this->app_params['ask_user_before_reload_last_session'] &&
            !empty($this->session_data) &&
            $this->session_data['current_menu_id'] !== WELCOME_MENU_NAME
        ) {
            $this->setCustomUssdRequestType(APP_REQUEST_ASK_USER_BEFORE_RELOAD_LAST_SESSION);
        } else {
            $this->setCustomUssdRequestType(APP_REQUEST_RELOAD_LAST_SESSION_DIRECTLY);
        }
    }

    protected function handleUserRequest()
    {
        switch ($this->ussdRequestType()) {
            case APP_REQUEST_INIT:
                $this->runWelcomeState();
                break;

            case APP_REQUEST_USER_SENT_RESPONSE:
                if ($this->hasComeBackAfterNoTimeoutFinalResponse()) {
                    $this->allowTimeout();
                    $this->hardEnd();
                } elseif ($this->ussdHasSwitched()) {
                    $this->processFromRemoteUssd();
                } else {
                    $this->processResponse($this->currentMenuId());
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
                $this->hardEnd('REQUEST CANCELLED');
                break;

            default:
                $this->hardEnd('UNKNOWN USSD SERVICE OPERATOR');
                break;
        }
    }

    public function hasComeBackAfterNoTimeoutFinalResponse()
    {
        return $this->mustNotTimeout() && empty($this->session->data());
    }

    public function hydrateRequestParams($request_params)
    {
        // $request_params['msisdn'] = trim($request_params['msisdn'], '+');

        foreach (ALLOWED_REQUEST_PARAMS as $param_name) {
            $this->request_params[$param_name] = $this->sanitizePostvar($request_params[$param_name]);
        }

        if (isset($this->request_params['msisdn'])) {
            $this->request_params['msisdn'] = Str::internationaliseNumber($this->request_params['msisdn']);
        }

        if (isset($request_params['channel'])) {
            $this->request_params['channel'] = strtoupper($this->sanitizePostvar($request_params['channel']));
        }
    }

    public function hydrateAppParams()
    {
        $app_params = require_once $this->config('app_config_path');
        $this->app_params = array_merge($this->app_params, $app_params);
    }

    public function sanitizePostvar($var)
    {
        return htmlspecialchars(addslashes(urldecode($var)));
    }

    protected function runLastSessionState()
    {
        // current_menu_id has been retrieved from the last state
        $this->runState($this->currentMenuId());
    }

    public function currentMenuId()
    {
        if (!isset($this->session_data['current_menu_id'])) {
            return 'welcome';
        }

        return $this->session_data['current_menu_id'];
    }

    protected function setCurrentMenuId($id)
    {
        $this->session_data['current_menu_id'] = $id;

        return $this;
    }

    protected function runAskUserBeforeReloadLastSessionState()
    {
        $this->runState(ASK_USER_BEFORE_RELOAD_LAST_SESSION);
    }

    protected function processResponse($menu_id)
    {
        /*
         * Do not use empty() to check the user response. The expected response
         * can for e.g. be 0 (zero), which empty() sees like empty.
         */
        if ($this->userResponse() === '') {
            $this->runInvalidInputState('Empty response not allowed');
            return;
        }

        $response = $this->userResponse();

        $this->loadMenuEntity($menu_id, 'current_menu_entity');

        $response_exists_in_menu_actions =
        isset($this->menus[$menu_id][ACTIONS][$response][ITEM_ACTION]);

        $next_menu_id = $this->menus->getNextMenuId(
            $response,
            $menu_id,
            $response_exists_in_menu_actions
        );

        $user_error = $next_menu_id === false;

        if (
            isset($this->menus[$menu_id][ACTIONS][DEFAULT_MENU_ACTION]) &&
            !$user_error &&
            !$response_exists_in_menu_actions &&
            !in_array($next_menu_id, RESERVED_MENU_IDs)
        ) {
            $response_valid = $this->validateUserResponse(
                $response,
                $menu_id,
                $next_menu_id
            );

            $user_error = !$response_valid;
        }

        if ($user_error) {
            if ($this->app_params['end_on_user_error']) {
                $this->hardEnd($this->default_error_msg);
            } else {
                $this->runInvalidInputState();
            }

            return;
        }

        if (!$this->menus->menuStateExists($next_menu_id)) {
            $this->addWarningInSimulator(
                'The menu `' . $next_menu_id . '` cannot be found.'
            );

            if ($this->app_params['end_on_unhandled_action']) {
                $this->hardEnd('Action not handled.');
            } else {
                $this->runInvalidInputState('Action not handled.');
            }

            return;
        }

        $this->setNextMenuId($next_menu_id);

        $this->saveUserResponse($response);

        $this->runAfterMenuFunction(
            $response,
            $menu_id,
            $next_menu_id,
            $response_exists_in_menu_actions
        );

        /*
         * If the next_menu_id is an url then we switch to that USSD
         * application.
         * For this application to retake control, consider switching back
         * from the remote application to this application.
         * But it is only possible if the remote application is using
         * this ussd library or implements a method of switching to another
         * ussd.
         * For the "switching back" ability to work properly both the parameters
         * "always_start_new_session" and "ask_user_before_reload_last_session"
         * have to be set to false.
         */
        if (URL::isUrl($next_menu_id)) {
            return $this->switchToRemoteUssd($next_menu_id);
        }

        return $this->runAppropriateState($next_menu_id);
    }

    /**
     * Load Menu Entity
     *
     * @param string $class
     * @param string $type ('current'|'next")
     * @return void
     */
    public function loadMenuEntity($menu_id, $entity_type)
    {
        $menu_entity_class = $this->menuEntityClass($menu_id);

        if (class_exists($menu_entity_class)) {
            $this->$entity_type = new $menu_entity_class;
            $this->$entity_type->setApp($this);
        }
    }

    protected function runAppropriateState($next_menu_id)
    {
        switch ($next_menu_id) {
            case APP_BACK:
                $this->runPreviousState();
                break;

            case APP_SPLITTED_MENU_NEXT:
                $this->runSameStateNextPage();
                break;

            case APP_END:
                $this->hardEnd();
                break;

            case APP_WELCOME:
                $this->runWelcomeState();
                break;

            case APP_SAME:
                $this->runSameState();
                break;

            case APP_CONTINUE_LAST_SESSION:
                $this->setCurrentMenuId($this->backHistoryPop());
                $this->runLastSessionState();
                break;

            default:
                $this->runState($next_menu_id);
                break;
        }
    }

    protected function switchToRemoteUssd($next_menu_id)
    {
        $this->session_data['switched_ussd_endpoint'] = $next_menu_id;
        $this->session_data['ussd_has_switched'] = true;

        $this->session->save($this->session_data);

        $this->setUssdRequestType(APP_REQUEST_INIT);

        return $this->processFromRemoteUssd($next_menu_id);
    }

    protected function saveUserResponse($user_response)
    {
        $id = $this->currentMenuId();
        $to_save = $user_response;
        $already_got_save_as_response = false;

        $method = MENU_ENTITY_SAVE_RESPONSE_AS;

        if (
            // !(
            //     $user_response_exists_in_menu_actions &&
            //     in_array($next_menu_id, RESERVED_MENU_IDs, true)
            // ) &&
            $this->current_menu_entity &&
            method_exists($this->current_menu_entity, $method)
        ) {
            $to_save = call_user_func(
                [$this->current_menu_entity, $method],
                $to_save, $this->userPreviousResponses()
            );

            if ($to_save === null) {
                $this->addWarningInSimulator('The method `' . $method .
                    '` in the class ' . $this->current_menu_entity . ' returns `NULL`.
                 That may means the method does not return anything or you are
                  deliberately returning NULL. <strong>NULL will be saved as
                  the user\'s response</strong>! Check that method (' .
                    $this->current_menu_entity . '::' . $method . ') if you think it
                  must return something else.'
                );
            }

            $already_got_save_as_response = true;
        }

        if (
            isset($this->menus[$id][ACTIONS][$user_response][SAVE_RESPONSE_AS])
        ) {
            if (!$already_got_save_as_response) {
                $to_save = $this->menus[$id]
                    [ACTIONS]
                    [$user_response]
                    [SAVE_RESPONSE_AS];
                $already_got_save_as_response = true;
            } else {
                $this->addWarningInSimulator('There is a `' . $method .
                    '` method in the class ' . $this->current_menu_entity . ' while this menu (' . $id . ') contains a `' . SAVE_RESPONSE_AS . '` attribute. The `' . $method .
                    '` method has precedence on the menu attribute. Its return value will be used as the user\'s response instead of the `' . SAVE_RESPONSE_AS . '` attribute.'
                );
            }
        }

        $this->userPreviousResponsesAdd($to_save);
    }

    public function validateFromMenu($menu_id, $response)
    {
        if (!isset($this->menus[$menu_id][ACTIONS][VALIDATE])) {
            return true;
        }

        $rules = $this->menus[$menu_id][ACTIONS][VALIDATE];

        $validation = UserResponseValidator::validate($response, $rules);

        if (!$validation->validated) {
            $this->error .= "\n" . $validation->error;
        }

        return $validation->validated;
    }

    public function validateFromMenuEntity($next_menu_id, $response)
    {
        $validate_method = MENU_ENTITY_VALIDATE_RESPONSE;

        if (
            !in_array($next_menu_id, RESERVED_MENU_IDs, true) &&
            $this->current_menu_entity &&
            method_exists($this->current_menu_entity, $validate_method)
        ) {

            $validated = call_user_func(
                [$this->current_menu_entity, $validate_method],
                $response, $this->userPreviousResponses()
            );

            if (!is_bool($validated)) {
                throw new \Exception('The method `' . $validate_method . '` inside `' . $this->current_menu_entity . '` class must return a boolean.');
            }

            return $validated;
        }

        return true;
    }

    protected function validateUserResponse(
        $response,
        $menu_id,
        $next_menu_id
    ) {
        $validated = $this->validateFromMenu($menu_id, $response);

        return $validated ? $this->validateFromMenuEntity($next_menu_id, $response) : $validated;
    }

    protected function runAfterMenuFunction(
        $user_response,
        $menu_id,
        $next_menu_id,
        $user_response_exists_in_menu_actions
    ) {
        /*
         * The "after_" method does not have to be called if the response
         * expected has been defined by the developper and is an app action
         * (e.g. in the case of the APP_BACK action, the response defined by
         * developper could be 98. If the user provide 98 we don't need to call
         * the "after_" method). This is to allow the developer to use the
         * "after_" method just for checking the user's response that leads to
         * his (the developer) other menu. The library takes care of the app
         * actions.
         */
        $after_method = MENU_ENTITY_AFTER;

        if (
            !(
                $user_response_exists_in_menu_actions &&
                in_array($next_menu_id, RESERVED_MENU_IDs, true)
            ) &&
            $this->current_menu_entity &&
            method_exists($this->current_menu_entity, $after_method)
        ) {
            call_user_func(
                [$this->current_menu_entity, $after_method],
                $user_response, $this->userPreviousResponses()
            );
        }
    }

    protected function processFromRemoteUssd($endpoint = '')
    {
        $endpoint = $endpoint ? $endpoint : $this->switchedUssdEndpoint();

        $response = HTTP::post($this->request_params, $endpoint);

        if ($response['SUCCESS']) {
            $this->sendRemoteResponse($response['data']);
        } else {
            $this->sendRemoteResponse($response['error']);
        }
    }

    protected function formatResponse($message, $request_type)
    {
        $fields = array(
            'message' => trim($message),
            'ussdServiceOp' => $request_type,
            'sessionID' => $this->sessionId(),
        );

        if ($this->warning_in_simulator) {
            $fields['WARNING'] = $this->warning_in_simulator;
        }

        if ($this->info_in_simulator) {
            $fields['INFO'] = $this->info_in_simulator;
        }

        return json_encode($fields);
    }

    protected function sendResponse($message, $ussd_request_type = APP_REQUEST_ASK_USER_RESPONSE, $hard = false)
    {
        /*
         * Sometimes, we need to send the response to the user and do
         * another staff before ending the script. Those times, we just
         * need to echo the response. That is the soft response snding.
         * Sometimes we need to terminate the script immediately when sending
         * the response; for exemple when the developer himself will call the
         * end function from his code.
         */
        if ($hard) {
            exit($this->formatResponse($message, $ussd_request_type));
        } else {
            /*
             * All these ob_start, ob_flush, etc are just to be able to send the
             * response BUT continue the script (so that the user receive
             * the response faster, as the USSD times out very quickly)
             *
             * ``ignore_user_abort(true);``  Not really needed here
             * (useful if it was in a browser or cgi where the user
             * can abort the request.)
             */
            ignore_user_abort(true);

            /*
             * ``set_time_limit(0);``
             * In case the script is taking longer than the PHP default
             * execution time limit
             */
            set_time_limit(0);
            ob_start();

            echo $this->formatResponse($message, $ussd_request_type);

            header('Content-Encoding: none');
            header('Content-Length: ' . ob_get_length());
            header('Connection: close');
            ob_end_flush();
            ob_flush();
            flush();
        }
    }

    protected function sendFinalResponse($message, $hard = false)
    {
        if ($this->isUssdChannel() && $this->mustNotTimeout()) {
            $this->sendResponse($message, APP_REQUEST_ASK_USER_RESPONSE, $hard);
        } else {
            $this->sendResponse($message, APP_REQUEST_END, $hard);
        }

        $this->session->resetData();
    }

    public function hardEnd($message = '')
    {
        $this->end($message);
    }

    public function softEnd($message = '')
    {
        $this->end($message, false);
    }

    protected function sendRemoteResponse($resJSON)
    {
        $response = json_decode($resJSON, true);

        /*
         * Important! To notify the developer that the error occured at
         * the remote ussd side and not at this ussd switch side.
         */
        if (!is_array($response)) {
            echo "ERROR OCCURED AT THE REMOTE USSD SIDE:  " . $resJSON;
            return;
        }

        echo $resJSON;
    }

    public function switchedUssdEndpoint()
    {
        if (isset($this->session_data['switched_ussd_endpoint'])) {
            return $this->session_data['switched_ussd_endpoint'];
        }

        return '';
    }

    public function ussdHasSwitched()
    {
        return isset($this->session_data['ussd_has_switched']) ? $this->session_data['ussd_has_switched'] : false;
    }

    protected function runSameStateNextPage()
    {
        $this->userPreviousResponsesPop($this->currentMenuId());

        $this->runNextState(APP_SPLITTED_MENU_NEXT);
    }

    protected function getErrorIfExists($menu_id)
    {
        if ($this->error() && $menu_id === $this->currentMenuId()) {
            return $this->error();
        }

        return '';
    }

    protected function callFeedMenuMessageHook($menu_id)
    {
        $result_call_before = '';

        $call_before = MENU_ENTITY_MESSAGE;

        if (
            $this->next_menu_entity &&
            method_exists($this->next_menu_entity, $call_before)
        ) {
            $result_call_before = call_user_func(
                [$this->next_menu_entity, $call_before],
                $this->userPreviousResponses()
            );
        }

        if (isset($this->menus[$menu_id][MSG])) {
            if (
                !is_string($result_call_before) &&
                !is_array($result_call_before)
            ) {
                throw new \Exception("STRING OR ARRAY EXPECTED.\nThe function '" . $call_before . "' must return either a string or an associative array. If it returns a string, the string will be appended to the message of the menu. If it return an array, the library will parse the menu message and replace all words that are in the form :indexofthearray: by the value associated in the array. Check the documentation to learn more on how to use 'feed_' functions.");
            }
        } else {
            if (!is_string($result_call_before)) {
                throw new \Exception("STRING EXPECTED.\nThe function '" . $call_before . "' must return a string if the menu itself does not have any message. Check the documentation to learn more on how to use 'feed_' functions.");
            }
        }

        return $result_call_before;
    }

    protected function callFeedActionsHook($menu_id)
    {
        $result_call_before = [];

        $call_before = MENU_ENTITY_ACTIONS;

        if (
            $this->next_menu_entity &&
            method_exists($this->next_menu_entity, $call_before)
        ) {
            $result_call_before = call_user_func(
                [$this->next_menu_entity, $call_before],
                $this->userPreviousResponses()
            );
        }

        if (!is_array($result_call_before)) {
            throw new \Exception("ARRAY EXPECTED.\nThe method '" . $call_before . "' must return an associative array.");
        }

        return $result_call_before;
    }

    protected function callBeforeHook($menu_id)
    {
        $call_before = MENU_ENTITY_BEFORE;

        if (
            $this->next_menu_entity &&
            method_exists($this->next_menu_entity, $call_before)
        ) {
            call_user_func(
                [$this->next_menu_entity, $call_before],
                $this->userPreviousResponses()
            );
        }
    }

    protected function runState($next_menu_id)
    {
        $this->loadMenuEntity($next_menu_id, 'next_menu_entity');

        $this->callBeforeHook($next_menu_id);

        // The softEnd or harEnd method can be called inside
        // the `before` method. If so, we terminate the script here
        if ($this->end_method_already_called) {
            exit;
        }

        $msg = $this->currentMenuMsg($next_menu_id);

        $actions = [];

        if ($this->isLastMenuPage($next_menu_id)) {
            $is_ussd_channel = $this->isUssdChannel();

            $this->sms = $msg;

            if (
                $is_ussd_channel &&
                $this->mustNotTimeout() &&
                $this->app_params['cancel_msg']
            ) {
                $msg .= "\n\n" . $this->app_params['cancel_msg'];
            }

            if (
                !$is_ussd_channel ||
                ($is_ussd_channel &&
                    !$this->contentOverflows($msg))
            ) {
                $this->runLastState($msg);
                return;
            }
        } else {
            $actions = $this->currentMenuActions($next_menu_id);
        }

        $this->runNextState(
            $next_menu_id,
            $msg,
            $actions,
            $this->current_menu_has_back_action
        );
    }

    public function isUssdChannel()
    {
        return $this->channel() === 'USSD';
    }

    public function mustNotTimeout()
    {
        return $this->app_params['allow_timeout'] === false;
    }

    public function allowTimeout()
    {
        $this->app_params['allow_timeout'] = true;
    }

    public function contentOverflows($msg)
    {
        return strlen($msg) > $this->max_ussd_page_content || count(explode("\n", $msg)) > $this->max_ussd_page_lines;
    }

    protected function isLastMenuPage($menu_id)
    {
        return !isset($this->menus[$menu_id][ACTIONS]);
    }

    protected function currentMenuMsg($next_menu_id)
    {

        $msg = $this->getErrorIfExists($next_menu_id);

        $result_call_before = $this->callFeedMenuMessageHook($next_menu_id);

        if (isset($this->menus[$next_menu_id][MSG])) {
            $menu_msg = $this->menus[$next_menu_id][MSG];

            if (is_string($result_call_before)) {
                if (empty($menu_msg)) {
                    $menu_msg = $result_call_before;
                } else {
                    $menu_msg = $result_call_before ? $result_call_before . "\n" . $menu_msg : $menu_msg;
                }
            } elseif (is_array($result_call_before)) {
                foreach ($result_call_before as $pattern_name => $value) {
                    $pattern = '/' . MENU_MSG_PLACEHOLDER . $pattern_name . MENU_MSG_PLACEHOLDER . '/';
                    $menu_msg = preg_replace($pattern, $value, $menu_msg);
                }
            }

            $msg .= $msg ? "\n" . $menu_msg : $menu_msg;
        } else {
            if (empty($msg)) {
                $msg = $result_call_before;
            } else {
                $msg = $result_call_before ? $result_call_before . "\n" . $msg : $msg;
            }
        }

        return $msg;
    }

    protected function currentMenuActions($next_menu_id)
    {
        $this->callFeedActionsHook($next_menu_id);
        $has_back_action = false;

        $actions = [];
        $menu = $this->menus[$next_menu_id][ACTIONS];

        foreach ($menu as $index => $value) {
            // var_dump($index);

            // if ($index == '0') {
            //     // var_dump($menu);
            //     var_dump(in_array('0', RESERVED_MENU_ACTIONS));
            //     var_dump(in_array(0, RESERVED_MENU_ACTIONS));
            //     var_dump(in_array('1', RESERVED_MENU_ACTIONS));
            //     var_dump(in_array('validate', RESERVED_MENU_ACTIONS));
            //     var_dump(in_array($index, RESERVED_MENU_ACTIONS));
            //     var_dump($index);

            // }

            if ($index == '0' || array_search($index, RESERVED_MENU_ACTIONS) === false) {
                $actions[$index] = $value[ITEM_MSG];

                if (
                    !$has_back_action &&
                    isset($value[ITEM_ACTION]) &&
                    $value[ITEM_ACTION] === APP_BACK
                ) {
                    $has_back_action = true;
                }
            }
        }

        $this->current_menu_has_back_action = $has_back_action;

        return $actions;
    }

    protected function runWelcomeState()
    {
        if (!$this->menus->has(WELCOME_MENU_NAME)) {
            throw new \Exception('No welcome menu defined. There must be at least one menu named `welcome` which will be the first displayed menu.');
        }

        $this->session_data = [];
        $this->runState(WELCOME_MENU_NAME);
    }

    protected function runNextState(
        $next_menu_id,
        $msg = '',
        $menu_actions = [],
        $has_back_action = false
    ) {
        $menu_string = '';

        if ($next_menu_id === APP_SPLITTED_MENU_NEXT) {
            $menu_string = $this->menus->getSplitMenuStringNext();
            $has_back_action = $this->session_data['current_menu_has_back_action'];

        } elseif ($next_menu_id === APP_SPLITTED_MENU_BACK) {
            $menu_string = $this->menus->getSplitMenuStringBack();
            $has_back_action = $this->session_data['current_menu_has_back_action'];

        } else {
            $menu_string = $this->menus->getMenuString(
                $menu_actions,
                $msg,
                $has_back_action
            );
        }

        // $this->sendResponse($menu_string);

        if (
            $next_menu_id !== APP_SPLITTED_MENU_NEXT &&
            $next_menu_id !== APP_SPLITTED_MENU_BACK
        ) {
            if (
                $this->currentMenuId() &&
                $this->currentMenuId() !== WELCOME_MENU_NAME &&
                $next_menu_id !== ASK_USER_BEFORE_RELOAD_LAST_SESSION &&
                !empty($this->backHistory()) &&
                $next_menu_id === $this->previousMenuId()
            ) {
                $this->backHistoryPop();
            } elseif (
                $this->currentMenuId() &&
                $next_menu_id !== $this->currentMenuId() &&
                $this->currentMenuId() !== ASK_USER_BEFORE_RELOAD_LAST_SESSION
            ) {
                $this->backHistoryPush($this->currentMenuId());
            }

            $this->setCurrentMenuId($next_menu_id);
        }

        $this->session->save($this->session_data);
        $this->sendResponse($menu_string);
    }

    public function runLastState($msg = '')
    {
        $msg = trim($msg);

        /*
         * In production, for timeout reason, push immediately the response
         * before doing any other thing, especially calling an API, like
         * sending a message, which may take time.
         */
        if ($this->app_params['environment'] !== DEV) {
            $this->softEnd($msg);
        }

        if ($msg && $this->app_params['always_send_sms_at_end']) {
            $sms = $this->sms ? $this->sms : $msg;
            $this->sendSms($sms);
        }

        /*
         * In development, pushing the response to the user will rather be
         * the last thing, to be able to receive any ever error, warning or
         * info in the simulator.
         */
        if ($this->app_params['environment'] === DEV) {
            $this->softEnd($msg);
        }

        // $this->session->resetData();
    }

    protected function runPreviousState()
    {
        $this->userPreviousResponsesPop($this->currentMenuId());

        if (
            isset($this->session_data['current_menu_splitted']) &&
            $this->session_data['current_menu_splitted'] &&
            isset($this->session_data['current_menu_split_index']) &&
            $this->session_data['current_menu_split_index'] > 0
        ) {
            $this->runNextState(APP_SPLITTED_MENU_BACK);
        } else {
            $previous_menu_id = $this->previousMenuId();
            $this->userPreviousResponsesPop($previous_menu_id);
            $this->runState($previous_menu_id);
        }
    }

    protected function userPreviousResponsesPop($menu_id)
    {
        if ($this->userPreviousResponses()) {
            if (
                isset($this->session_data['user_previous_responses'][$menu_id]) &&
                is_array($this->session_data['user_previous_responses'][$menu_id])
            ) {
                return array_pop($this->session_data['user_previous_responses'][$menu_id]);
            }
        }

        return null;
    }

    protected function userPreviousResponsesAdd($response)
    {
        $id = $this->currentMenuId();

        if (
            !isset($this->userPreviousResponses()[$id]) ||
            !is_array($this->userPreviousResponses()[$id])
        ) {
            $this->session_data['user_previous_responses'][$id] = [];
        }

        $this->session_data['user_previous_responses'][$id][] = $response;
    }

    public function userPreviousResponses($menu_id = null)
    {
        $responses = isset($this->session_data['user_previous_responses']) ?
        new UserResponse($this->session_data['user_previous_responses']) :
        new UserResponse([]);

        return $menu_id ? $responses[$menu_id] : $responses;
    }

    protected function runSameState()
    {
        $this->userPreviousResponsesPop($this->currentMenuId());
        $this->runState($this->currentMenuId());
    }

    protected function runInvalidInputState($error = '')
    {
        if ($error) {
            $this->setError($error);
        } else {
            $error = empty($this->error()) ? $this->app_params['default_error_msg'] : $this->error();
            $this->setError($error);
        }

        $this->runState($this->currentMenuId());
    }

    public function end($sentMsg = '', $hard = true)
    {
        $this->end_method_already_called = true;
        $msg = $sentMsg === '' ? $this->app_params['default_end_msg'] : $sentMsg;
        $this->sendFinalResponse($msg, $hard);
    }

    /**
     * The formatter is removing the public|protected when the method name
     * is 'exit'
     * TO BE REVIEWED.
     */
    function exit($msg = '') {
        $this->hardEnd($msg);
    }

    public function modifyCurrentPageActions($actions)
    {
        $menu_id = $this->nextMenuId() ? $this->nextMenuId() : $this->currentMenuId();

        return $this->modifyPageActions($actions, $menu_id);
    }

    public function modifyPageActions($actions, $menu_id)
    {
        if (!isset($this->session_data[MODIFY_MENUS])) {
            $this->session_data[MODIFY_MENUS] = [
                $menu_id => [ACTIONS => []],
            ];
        }

        foreach ($actions as $key => $value) {
            $this->session_data[MODIFY_MENUS][$menu_id][ACTIONS][$key] = $value;
        }

        return $this->menus->modifyPageActions($actions, $menu_id);
    }

    protected function setNextMenuId($id)
    {
        $this->next_menu_id = $id;
    }

    public function nextMenuId()
    {
        return $this->next_menu_id;
    }

    public function previousMenuId()
    {
        $length = count($this->backHistory());

        if (!$length) {
            throw new \Exception("Can't get a previous menu. 'back_history' is empty.");
        }

        return $this->backHistory()[$length - 1];
    }

    /**
     * Allow developer to save a value in the session
     */
    public function sessionSave($name, $value)
    {
        if (!isset($this->session_data[DEVELOPER_SAVED_DATA])) {
            $this->session_data[DEVELOPER_SAVED_DATA] = [];
        }

        $this->session_data[DEVELOPER_SAVED_DATA][$name] = $value;
    }

    /**
     * Allow developer to retrieve information (s)he saved in the session.
     */
    public function sessionGet($name)
    {
        if (!isset($this->session_data[DEVELOPER_SAVED_DATA][$name])) {
            throw new \Exception('Index "' . $name . '" not found in the session data.');
        }

        return $this->session_data[DEVELOPER_SAVED_DATA][$name];
    }

    /**
     * Allow developer to check if the session contains an index.
     */
    public function sessionHas($name)
    {
        return isset($this->session_data[DEVELOPER_SAVED_DATA][$name]);
    }

    public function backHistory()
    {
        if (!isset($this->session_data['back_history'])) {
            $this->session_data['back_history'] = [];
        }

        return $this->session_data['back_history'];
    }

    protected function backHistoryPush($menu_id)
    {
        if (!isset($this->session_data['back_history'])) {
            $this->session_data['back_history'] = [];
        }

        return array_push($this->session_data['back_history'], $menu_id);
    }

    protected function backHistoryPop()
    {
        if (!isset($this->session_data['back_history'])) {
            $this->session_data['back_history'] = [];
        }

        return array_pop($this->session_data['back_history']);
    }

    public function loadAppDBs()
    {
        $this->app_DBs = Database::loadAppDBs();
        $this->app_db_loaded = true;
    }

    public function db($id = '')
    {
        if ($this->app_params['connect_app_db']) {
            $id = $id === '' ? 'default' : $id;

            if (!$this->app_db_loaded) {
                $this->loadAppDBs();
            }

            if ($id === 'default' && !isset($this->app_DBs['default'])) {
                throw new \Exception('No default database set! Kindly update your database configurations in "config/database.php". <br/> At least one database has to have the index "default" in the array return in "config/database.php". If not, you will need to specify the name of the database you want to load.');
            } elseif (!isset($this->app_DBs[$id])) {
                throw new \Exception('No database configuration set with the name "' . $id . '" in "config/database.php"!');
            }

            return $this->app_DBs[$id];
        } else {
            throw new \Exception('Database not connected. Please set "connect_app_db" to boolean `true` in the "config/app.php" to enable connection to the database.');
        }
    }

    public function maxUssdPageContent()
    {
        return $this->max_ussd_page_content;
    }

    public function maxUssdPageLines()
    {
        return $this->max_ussd_page_lines;
    }

    public function createAppNamespace($prefix = '')
    {
        $namespace = Str::pascalCase($this->app_name);

        $pos = strpos(
            $namespace,
            $prefix,
            strlen($namespace) - strlen($prefix)
        );

        $not_already_prefixed = $pos === -1 || $pos !== 0;

        if ($not_already_prefixed) {
            $namespace .= $prefix;
        }

        return $namespace;
    }

    public function menusNamespace()
    {
        return $this->createAppNamespace(MENUS_NAMESPACE_PREFIX);
    }

    public function menuEntitiesNamespace()
    {
        return $this->createAppNamespace(MENU_ENTITIES_NAMESPACE_PREFIX);
    }

    public function menuEntityNamespace($menu_id)
    {
        return Str::pascalCase($menu_id);
    }

    public function menuEntityClass($menu_id)
    {
        return MENU_ENTITIES_NAMESPACE .
        $this->menuEntitiesNamespace() . '\\' .
        $this->menuEntityNamespace($menu_id);
    }

    public function currentMenuEntity()
    {
        return $this->current_menu_entity;
    }

    public function nextMenuEntity()
    {
        return $this->next_menu_entity;
    }

    public function sessionData()
    {
        return $this->session_data;
    }

    public function appParams()
    {
        return $this->app_params;
    }

    public function id()
    {
        return $this->app_params['id'];
    }

    public function error()
    {
        return $this->error;
    }

    public function msisdn()
    {
        return $this->request_params['msisdn'];
    }

    public function network()
    {
        return $this->request_params['network'];
    }

    public function sessionId()
    {
        return $this->request_params['sessionID'];
    }

    public function userResponse()
    {
        return $this->request_params['ussdString'];
    }

    public function channel()
    {
        return $this->request_params['channel'];
    }

    public function ussdRequestType()
    {
        if ($this->custom_ussd_request_type !== null) {
            return $this->custom_ussd_request_type;
        }

        return $this->request_params['ussdServiceOp'];
    }

    public function setError(string $error = '')
    {
        $this->error = $error;

        return $this;
    }

    protected function setUssdRequestType($request_type)
    {
        $possible_types = [
            APP_REQUEST_INIT,
            APP_REQUEST_END,
            APP_REQUEST_CANCELLED,
            APP_REQUEST_ASK_USER_RESPONSE,
            APP_REQUEST_USER_SENT_RESPONSE,
        ];

        if (!in_array($request_type, $possible_types)) {
            $msg = 'Trying to set a request type but the value provided "' . $request_type . '" is invalid.';
            throw new \Exception($msg);
        }

        $this->request_params['ussdServiceOp'] = $request_type;

        return $this;
    }

    protected function setCustomUssdRequestType($request_type)
    {
        $this->custom_ussd_request_type = $request_type;
    }

    public function setWarningInSimulator(array $warn)
    {
        $this->warning_in_simulator = $warn;
    }

    public function addWarningInSimulator($warn)
    {
        if (is_array($warn)) {
            array_merge($this->warning_in_simulator, $warn);
        } else {
            array_push($this->warning_in_simulator, $warn);
        }
    }

    public function setInfoInSimulator(array $info)
    {
        $this->info_in_simulator = $info;
    }

    public function addInfoInSimulator($info)
    {
        if (is_array($info)) {
            array_merge($this->info_in_simulator, $info);
        } else {
            array_push($this->info_in_simulator, $info);
        }
    }

    public function sendSms($msg, $msisdn = '', $sender_name = '')
    {
        $msisdn = $msisdn ? $msisdn : $this->msisdn();
        $sender_name = $sender_name ? $sender_name : $this->app_params['sms_sender_name'];

        $sms_data = array(
            'recipient' => trim($msisdn),
            'sender' => trim($sender_name),
            'message' => trim($msg),
        );

        $response = $this->apiSmsRequest($sms_data, $this->app_params['sms_endpoint']);

        $warnings = [];

        if ($response['error']) {
            $warnings['curl_error'] = $response['error'];
        }

        $result = $response['data'];

        if (isset($result['error']) && $result['error'] === false) {
            $warnings['sms_response'] = $result;
            $warnings['sms_data'] = $sms_data;
        }

        if (!empty($warnings)) {
            $this->addWarningInSimulator($warnings);
        }
    }

    public function apiSmsRequest($postvars, $endpoint)
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $endpoint);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postvars);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl_handle);
        $err = curl_error($curl_handle);

        curl_close($curl_handle);

        return [
            'data' => $result,
            'error' => $err,
        ];
    }

    public function config($name = null)
    {
        if (!$name) {
            return $this->config;
        } elseif ($this->config->has($name)) {
            return $this->config->get($name);
        }

        throw new \Exception('Key `' . $name . '` not found in the config');
    }

    /* public function sendSms(string $msg)
{
$result = SMS::send([
'message'   => $msg,
'recipient' => $this->msisdn(),
'sender'    => $this->app_params['sms_sender_name'],
'endpoint'  => $this->app_params['sms_endpoint'],
]);

if ($result['SUCCESS'] === false) {
$this->warning_in_simulator = [
'message' => 'Error while sending SMS',
'errors'  => $result['errors'],
];
}
} */
}
