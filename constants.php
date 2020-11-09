<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Defines Framework Constants.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

use Prinx\Config;
use Rejoice\Foundation\Path;

$paths = new Path;
$config = new Config($paths->get('app_config_dir'));

define('APP_REQUEST_INIT', $config->get('app.request_init'));
define('APP_REQUEST_END', $config->get('app.request_end'));
define('APP_REQUEST_FAILED', $config->get('app.request_failed'));
define('APP_REQUEST_CANCELLED', $config->get('app.request_cancelled'));
define('APP_REQUEST_ASK_USER_RESPONSE', $config->get('app.request_ask_user_response'));
define('APP_REQUEST_USER_SENT_RESPONSE', $config->get('app.request_user_sent_response'));
define(
    'APP_REQUEST_ASK_USER_BEFORE_RELOAD_LAST_SESSION',
    '__CUSTOM_REQUEST_TYPE1'
);
define(
    'APP_REQUEST_RELOAD_LAST_SESSION_DIRECTLY',
    '__CUSTOM_REQUEST_TYPE2'
);

define('MSG', 'message');
define('ACTIONS', 'actions');

define('ITEM_MSG', 'display');
define('ITEM_ACTION', 'next_menu');
define('ITEM_LATER', 'later');

define('SAVE_RESPONSE_AS', 'save_as');
define('DEFAULT_NEXT_MENU', 'default_next_menu');
define('MENU', 'menu');
define('VALIDATE', 'validate');
define('FORCED_MENU_FLOW', 'forced_menu_flow');

define('APP_WELCOME', '__welcome');
define('APP_BACK', '__back');
define('APP_SAME', '__same');
define('APP_END', '__end');
define('APP_SPLITTED_MENU_NEXT', '__split_next');
define('APP_SPLITTED_MENU_BACK', '__split_back');
define('APP_CONTINUE_LAST_SESSION', '__continue_last_session');
define('APP_PAGINATE_FORWARD', '__paginate_forward');
define('APP_PAGINATE_BACK', '__paginate_back');

/*
 * Actions refer to a certain type of special menu that the app can manage
 * automatically:
 *
 * APP_WELCOME: throw the welcome menu
 * APP_BACK: throw the previous menu
 * APP_SAME: re-throw the current menu
 * APP_END: throw a goodbye menu
 * APP_CONTINUE_LAST_SESSION: throw the menu on which the user was before
 * request timed out or was cancelled
 */
define('RESERVED_MENU_IDs', [
    APP_WELCOME,
    APP_END,
    APP_BACK,
    APP_SAME,
    APP_CONTINUE_LAST_SESSION,
    APP_SPLITTED_MENU_NEXT,
    APP_SPLITTED_MENU_BACK,
    APP_PAGINATE_FORWARD,
    APP_PAGINATE_BACK,
]);

define('WELCOME_MENU_NAME', 'welcome');
define('MENU_MSG_DELIMITER', ':');

define(
    'REQUIRED_REQUEST_PARAMS',
    [
        $config->get('app.request_param_user_phone_number'),
        $config->get('app.request_param_user_network'),
        $config->get('app.request_param_session_id'),
        $config->get('app.request_param_user_response'),
        $config->get('app.request_param_request_type'),
    ]
);

define('ALLOWED_REQUEST_CHANNELS', ['USSD', 'WHATSAPP', 'CONSOLE']);

define(
    'ASK_USER_BEFORE_RELOAD_LAST_SESSION',
    'ask_user_before_reload_last_session'
);

define('DEVELOPER_SAVED_DATA', 'session_data_accessible_by_app');

define('CURRENT_MENU_ACTIONS', 'modify_menus');

define('RESERVED_MENU_ACTIONS', [
    DEFAULT_NEXT_MENU,
    VALIDATE,
]);

define('MENU_ENTITY_VALIDATE_RESPONSE', 'validate');
define('MENU_ENTITY_SAVE_RESPONSE_AS', 'saveAs');
define('MENU_ENTITY_DEFAULT_NEXT_MENU', 'defaultNextMenu');
define('MENU_ENTITY_MESSAGE', 'message');
define('MENU_ENTITY_ACTIONS', 'actions');
define('MENU_ENTITY_BEFORE', 'before');
define('MENU_ENTITY_AFTER', 'after');
define('MENU_ENTITY_ON_MOVE_TO_NEXT_MENU', 'onMoveToNextMenu');
define('MENU_ENTITY_ON_BACK', 'onBack');
define('MENU_ENTITY_ON_PAGINATE_FORWARD', 'onPaginateForward');
define('MENU_ENTITY_ON_PAGINATE_BACK', 'onPaginateBack');

define('MENU_ENTITIES_NAMESPACE', 'App\\Menus\\');

define('MENU_HOOKS', [
    MENU_ENTITY_VALIDATE_RESPONSE,
    MENU_ENTITY_SAVE_RESPONSE_AS,
    MENU_ENTITY_DEFAULT_NEXT_MENU,
    MENU_ENTITY_MESSAGE,
    MENU_ENTITY_ACTIONS,
    MENU_ENTITY_BEFORE,
    MENU_ENTITY_AFTER,
    MENU_ENTITY_ON_MOVE_TO_NEXT_MENU,
    MENU_ENTITY_ON_BACK,
    MENU_ENTITY_ON_PAGINATE_FORWARD,
    MENU_ENTITY_ON_PAGINATE_BACK,
]);

define('RECEIVE_USER_RESPONSE', [
    MENU_ENTITY_VALIDATE_RESPONSE,
    MENU_ENTITY_SAVE_RESPONSE_AS,
    MENU_ENTITY_AFTER,
    MENU_ENTITY_ON_MOVE_TO_NEXT_MENU,
]);

define('DEFAULT_NAMESPACE', 'Rejoice\\');

define('DEV_ENV', ['dev', 'development', 'local', 'staging']);
