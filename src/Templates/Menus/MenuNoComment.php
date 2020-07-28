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

    public function message(\$userPreviousResponses)
    {
        return \"\";
    }

    public function actions(\$userPreviousResponses)
    {
        \$actions = [];

        return \$this->mergeAction(\$actions, \$this->backAction());
    }";

if ($validate) {
    $template .= "

    public function validate(\$response)
    {
        return [
            'alpha' => 'Please, Only letters allowed',
            'minLen:4' => 'Enter, at least 4 letters',
            'maxLen:50' => '50 letters maximum',
        ];
    }";
}

if ($saveAs) {
    $template .= "

    public function saveAs(\$response)
    {
        return \$response;
    }";
}

if ($after) {
    $template .= "

    public function after(\$response, \$userPreviousResponses)
    {
        //
    }";
}

if ($onNext) {
    $template .= "

    public function onMoveToNextMenu(\$userPreviousResponses)
    {
        //
    }";
}

if ($onBack) {
    $template .= "

    public function onBack(\$userPreviousResponses)
    {
        //
    }";
}

$template .= "
}";

return $template;
