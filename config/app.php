<?php

use function Prinx\Dotenv\env;

return [

    /*
     * Unique identifier for this application.
     */
    'id' => env('APP_ID', 'rejoice_'.rand(1, 9999999)),

    /*
     * The environment of the application
     */
    'environment' => env('APP_ENV', 'prod'),

    /*
     * If the application can send SMS, specify the sender name to use.
     */
    'sms_sender_name' => env('SMS_SENDER_NAME', ''),

    /*
     * If the application can send SMS, specify the SMS api endpoint to use.
     */
    'sms_endpoint' => env('SMS_ENDPOINT', ''),

    /*
     * If true, and an SMS endpoint and sender name have been configured, every
     * last response will be sent as SMS to the user
     */
    'always_send_sms_at_end' => false,

    /*
     * For test purpose. You can enable/disable overall sending of SMS
     */
    'send_sms_enabled' => env('SEND_SMS_ENABLED', true),

    /*
     * For test purpose. You can enable/disable overall sending of SMS
     */
    'log_enabled' => env('LOG_ENABLED', true),

    /*
     * You can disabled connection to the application database by turning this to false
     */
    'connect_app_db' => true,

    /*
     * You can check the ussd code on every request to make sure the request is
     * coming from the expected ussd code. This will be irrelevant if the
     * application is not intended for USSD.
     */
    'validate_ussd_code' => false,

    /*
     * If true, every session will be destroyed whenever the user,
     * intentionally or unintentionaly, cancels his request.
     * If false, whenever the user comes back after
     * cancelling his session, they will have a prompt to continue from last
     * session or restart a new session
     */
    'always_start_new_session' => false,

    /*
     * This option works with the `always_start_new_session` option. If true
     * and always_start_new_session has been set to false, the user will have a
     * prompt to continue their last session or restart a new session. If
     * false, the last session will be automatically loaded whenever the user
     * comes back.
     * This does not have any effect if always_start_new_session is set to true.
     */
    'ask_user_before_reload_last_session' => true,

    /*
     * USSD sessions times out very quickly depending on the network and the
     * device of the user. When it happens, the user is not able to receive the
     * last response. They rather get an error message. But even if the timeout
     * time has passed, and a response is sent requesting for an input from the
     * user, that response is displayed. That behavior is used to allow the
     * application not to time out. So that the user can see the last response,
     * no matter how long the USSD menu is.
     */
    'allow_timeout' => true,

    /*
     * Cancel the session whenever there is an error in the user's response
     */
    'end_on_user_error' => false,

    /*
     * If true, cancel the session when the current response of the user should
     * lead to a menu but the menu has not been created by the developer.
     *
     * If false, the user will just receive an "Action not handled" error and
     * continue their session.
     */
    'end_on_unhandled_action' => false,

    /*
     * If true, you can directly call a sub menu, without passing through
     * the normal flow (from the welcome menu till the particular sub menu)
     */
    'allow_direct_sub_menu_call' => false,

];
