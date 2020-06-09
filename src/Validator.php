<?php
/**
 * Validate the request and the application parameters
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 * @license MIT
 */

namespace Prinx\Rejoice;

require_once 'constants.php';

class Validator
{
    public static function validateShortcode(
        $sent_shortcode,
        $defined_shortcode
    ) {
        if ($defined_shortcode === null) {
            exit('No "SHORTCODE" value found in the `.env` file. Kindly specify the shortcode application shortcode in the `.env` file.<br><br>Eg.<br>SHORTCODE="*380*75#"');
        }

        if ($sent_shortcode !== $defined_shortcode) {
            return false;
        }

        return true;
    }

    public static function validateMenuEntity($menu_entity)
    {
        (!method_exists($menu_entity, 'app_params') or
            (method_exists($menu_entity, 'app_params') &&
                !is_array($menu_entity->appParams()))
        ) and
        exit('The menu manager object sent must have a "app_params" method that return an array containing parameters.');
/*
(!method_exists($menu_entity, 'db_params') or
(method_exists($menu_entity, 'db_params') &&
!is_array($menu_entity->db_params()))
) and
exit('The menu manager object sent must have a "db_params" method that return an array containing the connections settings of the database where the USSD sessions will be stored.');
 */
        $params = $menu_entity->appParams();
        !isset($params['id']) and
        exit('The "app_params" must contain an "id" which value will be the id of the app.');

        isset($params['environment']) and
        !is_string($params['environment']) and
        exit("'environment' must be a string.");

        /*
        if (isset($params['environment']) && ($params['environment'] === PROD || $this->environment === PROD)) {
        return;
        }
         */

        self::validateStringParam($params['id'], 'id');

        isset($params['splitted_menu_display']) and
        !is_string($params['splitted_menu_display']) and
        exit("The parameter 'splitted_menu_display' must be a string.");

        isset($params['splitted_menu_next_thrower']) and
        !is_string($params['splitted_menu_next_thrower']) and
        exit("The parameter 'splitted_menu_next_thrower' must be a string.");

        isset($params['back_action_display']) and
        !is_string($params['back_action_display']) and
        exit("The parameter 'back_action_display' must be a string.");

        isset($params['back_action_thrower']) and
        !is_string($params['back_action_thrower']) and
        exit("The parameter 'back_action_thrower' must be a string.");

        isset($params['default_end_msg']) and
        !is_string($params['default_end_msg']) and
        exit("The parameter 'default_end_msg' must be a string.");

        /*
        isset($params['default_end_msg']) and
        strlen($params['default_end_msg']) > $this->ussd_lib->max_ussd_page_content and
        exit("The parameter 'default_end_msg' must not be longer than " . $this->ussd_lib->max_ussd_page_content . " characters.");
         */

        isset($params['default_error_msg']) and
        !is_string($params['default_error_msg']) and
        exit("The parameter 'default_error_msg' must be a string.");

        isset($params['always_start_new_session']) and
        !is_bool($params['always_start_new_session']) and
        exit("The parameter 'always_start_new_session' must be a boolean.");

        isset($params['always_start_new_session']) and
        !is_bool($params['always_start_new_session']) and
        exit("The parameter 'always_start_new_session' must be a boolean.");

        isset($params['ask_user_before_reload_last_session']) and
        !is_bool($params['ask_user_before_reload_last_session']) and
        exit("The parameter 'ask_user_before_reload_last_session' must be a boolean.");

        isset($params['always_send_sms']) and
        !is_bool($params['always_send_sms']) and
        exit("The parameter 'always_send_sms' must be a boolean.");

        isset($params['sms_sender_name']) and
        self::validateStringParam(
            $params['sms_sender_name'],
            'sms_sender_name',
            '/[a-z][a-z0-9+#$_@-]+/i',
            10
        );

        isset($params['sms_endpoint']) and
        !is_string($params['sms_endpoint']) and
        exit("The parameter 'sms_endpoint' must be a valid URL.");
    }

    public static function validateRequestParams($request_params)
    {
        if (!is_array($request_params)) {
            exit('Invalid request parameters received.');
        }

        foreach (ALLOWED_REQUEST_PARAMS as $value) {
            if (!isset($request_params[$value])) {
                exit("'" . $value . "' is missing in the request parameters.");
            }
        }

        if (
            isset($request_params['channel']) &&
            !in_array($request_params['channel'], ALLOWED_REQUEST_CHANNELS)
        ) {
            exit("Invalid parameter 'channel'.");
        }
    }

    public static function validateStringParam(
        $param,
        $param_name,
        $pattern = '/[a-z][a-z0-9]+/i',
        $max_length = 126,
        $min_length = 1
    ) {
        if (!is_string($param)) {
            exit('The parameter "' . $param_name . '" must be a string.');
        }

        if (strlen($param) < $min_length) {
            exit('The parameter "' . $param_name . '" is too short. It must be at least ' . $min_length . ' character(s).');
        }

        if (strlen($param) > $max_length) {
            exit('The parameter "' . $param_name . '" is too long. It must be at most ' . $max_length . ' characters.');
        }

        if (!preg_match($pattern, $param) === 1) {
            exit('The parameter "' . $param_name . '" contains unexpected character(s).');
        }

        return true;
    }

    public static function checkMenu($json_menu)
    {
        $all_menus = json_decode($json_menu, true, 512, JSON_THROW_ON_ERROR);

        $result = ['SUCCESS' => true, 'response' => []];

        if (!isset($all_menus[WELCOME_MENU_NAME])) {
            $result['SUCCESS'] = false;
            $result['response'][WELCOME_MENU_NAME]['errors'] = "There must be a menu named " . WELCOME_MENU_NAME . " that will be the welcome menu of the application";
        }

        foreach ($all_menus as $menu_name => $menu) {
            $infos = [];
            $errors = [];
            $warnings = [];

            if (!preg_match('/[a-z][a-z0-9_]+/i', $menu_name) !== 1) {
                $errors['about_menu_name'] = $menu_name . ' is an invalid menu name. Only letters, numbers and underscores are allowed.';
            }

            if (!isset($menu[MSG])) {
                $infos['about_message'] = "This menu does not have a message. It means will be generating a message from the 'before_" . $menu_name . "' function in your application, unless you don't want anything to be displayed above your menu items.";
            } elseif (isset($menu[MSG]) && !is_string($menu[MSG])) {
                $errors['about_message'] = 'The message of this menu must be a string.';
            }

            $actions_errors = [];

            if (!isset($menu[ACTIONS])) {
                $infos['about_actions'] = 'This menu does not have any following action. It will then be a final response.';
            } elseif (isset($menu[ACTIONS]) && !is_array($menu[ACTIONS])) {
                $actions_errors = 'The actions of this menu must be an array.';
            } else {
                foreach ($menu[ACTIONS] as $key => $value) {
                    if (!preg_match('/[a-z0-9_]+/i', $key) !== 1) {
                        $actions_errors[] = 'The key ' . $key . ' has an invalid format. Only letters, numbers and underscore are allowed.';
                    }

                    $next_menu = '';

                    if (is_array($value)) {
                        $next_menu = $value[ITEM_ACTION];
                    } elseif (is_string($value)) {
                        $next_menu = $value;
                    }

                    if (
                        empty($next_menu) ||
                        (!isset($all_menus[$next_menu]) &&
                            !in_array($next_menu, RESERVED_MENU_IDs, true))
                    ) {
                        $actions_errors[$next_menu] = 'The menu "' . $next_menu . '" has been associated as following menu to this menu but it has not yet been implemented.';
                    }
                }
            }

            if (!empty($actions_errors)) {
                $errors['about_actions'] = $actions_errors;
            }

            if (!isset($menu[MSG]) && !isset($menu[ACTIONS])) {
                $warnings = "This menu does not have any message and any menu. Make sure you are returning a menu message in the 'before_" . $menu_name . "' function.";
            }
            // END OF VERIFICATION

            if (!empty($errors) || !empty($warnings) || !empty($infos)) {
                $result['response'][$menu_name] = [];
            }

            if (!empty($errors)) {
                $result['response']['SUCCESS'] = false;
                $result['response'][$menu_name]['errors'] = $errors;
            }

            if (!empty($warnings)) {
                $result['response'][$menu_name]['warnings'] = $warnings;
            }

            if (!empty($infos)) {
                $result['response'][$menu_name]['infos'] = $infos;
            }
        }

        return $result;
    }
}
