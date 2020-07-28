<?php

$name = $name ?? 'NormalMenu';
$namespace = $namespace ?? 'App\Menus';

$validate = $validate ?? true;
$saveAs = $saveAs ?? true;
$after = $after ?? true;
$onNext = $onNext ?? true;
$onBack = $onBack ?? true;

// When the name of the menu is the same as one of the imported classes
$baseMenu = $baseMenuNamespaceLast = 'Menu';
if ($name === $baseMenu) {
    $baseMenu = 'BaseMenu';
    $baseMenuNamespaceLast .= " as $baseMenu";
}

$template = "<?php
namespace {$namespace};

use App\Menus\\$baseMenuNamespaceLast;

class $name extends $baseMenu
{
    /**
     * The message to display at the top of the screen
     *
     * @param Prinx\Rejoice\Foundation\UserResponse \$userPreviousResponses
     * @return string|array
     */
    public function message(\$userPreviousResponses)
    {
        return \"\";
    }

    /**
     * The actions to display at the bottom of the top message
     *
     * @param Prinx\Rejoice\Foundation\UserResponse \$userPreviousResponses
     * @return array
     */
    public function actions(\$userPreviousResponses)
    {
        \$actions = [];

        return \$this->mergeAction(\$actions, \$this->backAction());
    }";

if ($validate) {
    $template .= "

    /**
     * Validate the response of the user
     *
     * Must return a boolean (true if validation passed, false if not)
     * or a string of validation rules
     * or an array of validation rules (to customize the error messages)
     * or an instance of the UserResponseValidator
     *
     * @param string \$response
     * @return bool|array|Prinx\Rejoice\Foundation\UserResponseValidator
     */
    public function validate(\$response)
    {
        // Example
        return [
            'alpha' => 'Please, Only letters allowed',
            'minLen:4' => 'Enter, at least 4 letters',
            'maxLen:50' => '50 letters maximum',
        ];
    }";
}

if ($saveAs) {
    $template .= "

    /**
     * Modify the response of the user before it is saved to previous responses
     *
     * @param string \$response
     * @return mixed
     */
    public function saveAs(\$response)
    {
        return \$response;
    }";
}

if ($after) {
    $template .= "

    /**
     * This method will run after the response has been validated.
     * You can do here everything you want to do after the user has sent
     * their response
     *
     * @param string \$response
     * @param Prinx\Rejoice\Foundation\UserResponse \$userPreviousResponses
     * @return void
     */
    public function after(\$response, \$userPreviousResponses)
    {
        //
    }";
}

if ($onNext) {
    $template .= "

    /**
     * This method is similar to the 'after' method and will run after the
     * response, but only if the user is really moving to a menu up in the menu
     * flow. This means, this method will not for menus like __welcome, __same.
     *
     * @param Prinx\Rejoice\Foundation\UserResponse \$userPreviousResponses
     * @return void
     */
    public function onMoveToNextMenu(\$userPreviousResponses)
    {
        //
    }";
}

if ($onBack) {
    $template .= "

    /**
     * Runs whenever the user is moving back
     *
     * @param Prinx\Rejoice\Foundation\UserResponse \$userPreviousResponses
     * @return void
     */
    public function onBack(\$userPreviousResponses)
    {
        //
    }";
}

$template .= "
}";

return $template;
