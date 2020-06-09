<?php
/**
 * Framework Constants
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

define('APP_REQUEST_INIT', '1');
define('APP_REQUEST_END', '17');
define('APP_REQUEST_CANCELLED', '30');
define('APP_REQUEST_ASK_USER_RESPONSE', '2');
define('APP_REQUEST_USER_SENT_RESPONSE', '18');
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
define('SAVE_RESPONSE_AS', 'save_as');
define('DEFAULT_MENU_ACTION', 'default_next_menu');
define('VALIDATE', 'validate');

define('APP_WELCOME', '__welcome');
define('APP_BACK', '__back');
define('APP_SAME', '__same');
define('APP_END', '__end');
define('APP_SPLITTED_MENU_NEXT', '__split_next');
define('APP_SPLITTED_MENU_BACK', '__split_back');
define('APP_CONTINUE_LAST_SESSION', '__continue_last_session');

define('WELCOME_MENU_NAME', 'welcome');
define('MENU_MSG_PLACEHOLDER', ':');

define('PROD', 'prod');
define('DEV', 'dev');

define(
    'ALLOWED_REQUEST_PARAMS',
    [
        'msisdn',
        'network',
        'sessionID',
        'ussdString',
        'ussdServiceOp',
    ]
);

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
]);

define('ALLOWED_REQUEST_CHANNELS', ['USSD', 'WHATSAPP']);

define(
    'ASK_USER_BEFORE_RELOAD_LAST_SESSION',
    'ask_user_before_reload_last_session'
);

define('DEVELOPER_SAVED_DATA', 'session_data_accessible_by_app');

define('MODIFY_MENUS', 'modify_menus');

define('DEFAULT_LOG', realpath(__DIR__ . '/../../../../storage/logs/') . '/rejoice.log');

define('DEFAULT_CACHE', realpath(__DIR__ . '/../../../../storage/cache/') . '/rejoice.cache');
define('LOG_COUNT_CACHE', realpath(__DIR__ . '/../../../../storage/cache/') . '/log-count.cache');

define('RESERVED_MENU_ACTIONS', [
    DEFAULT_MENU_ACTION,
    VALIDATE,
]);

define('MENU_ENTITIES_NAMESPACE', 'App\MenuEntities\\');
define('MENU_ENTITY_VALIDATE_RESPONSE', 'validateResponse');
define('MENU_ENTITY_SAVE_RESPONSE_AS', 'saveResponseAs');
define('MENU_ENTITY_MESSAGE', 'message');
define('MENU_ENTITY_ACTIONS', 'actions');
define('MENU_ENTITY_BEFORE', 'before');
define('MENU_ENTITY_AFTER', 'after');
define('MENU_ENTITIES_NAMESPACE_PREFIX', 'MenuEntities');
define('MENUS_NAMESPACE_PREFIX', 'Menus');
