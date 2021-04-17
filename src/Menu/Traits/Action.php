<?php

namespace Rejoice\Menu\Traits;

/**
 * Menu actions related methods.
 */
trait Action
{
    /**
     * Merge an action array with an actionBag.
     *
     * @param array $actionBag
     * @param array $mergeWith
     *
     * @return array
     */
    public function mergeAction($actionBag, $mergeWith)
    {
        return array_replace($actionBag, $mergeWith);
    }

    /**
     * Add a `go to main menu` action into the actions.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array The modified action bag
     */
    public function insertMainMenuAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->mainMenuAction($trigger, $display));
    }

    /**
     * Return a `go to main menu` action bag.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
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
     * Insert a `go to previous menu` action into the actions.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array The modified action bag
     */
    public function insertBackAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->backAction($trigger, $display));
    }

    /**
     * Return an action bag containing a `go to previous menu` option, as an array.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
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
     * Return an action bag containing a `go to previous menu` option, as an array.
     *
     * Alias for the `backAction`.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array
     */
    public function back($trigger = '', $display = '')
    {
        return $this->backAction($trigger, $display);
    }

    /**
     * Insert a `paginate back` action into the actions.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array The modified action bag
     */
    public function insertPaginateBackAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->paginateBackAction($trigger, $display));
    }

    /**
     * Return a `paginate back` action, as an array.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
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
     * Insert a `paginate forward` action into the actions.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array The modified action bag
     */
    public function insertPaginateForwardAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->paginateForwardAction($trigger, $display));
    }

    /**
     * Return a `paginate forward` action.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
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
     * Insert a `end USSD` action into the actions.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
     * @return array The modified action bag
     */
    public function insertEndAction($trigger = '', $display = '')
    {
        return $this->insertMenuActions($this->endAction($trigger, $display));
    }

    /**
     * Return a `end USSD` action.
     *
     * If no trigger is passed, it will use the configured trigger
     * (in config/menu.php file).
     * Same for the display.
     *
     * @param string $trigger
     * @param string $display
     *
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
     * Return an action bag after adding the back action to it.
     *
     * @param array $actionBag
     *
     * @return array
     */
    public function withBack($actionBag = [], $backTrigger = '', $display = '')
    {
        return $this->mergeAction($actionBag, $this->backAction($backTrigger, $display));
    }

    /**
     * Defines the option the user will select to move back.
     *
     * @return string
     */
    public function backTrigger()
    {
        return $this->backTrigger ?? '';
    }

    /**
     * Delete all the actions of a particular menu page ($menuName).
     *
     * @param string $menuName
     *
     * @return void
     */
    public function emptyMenuActions($menuName = '')
    {
        $menuName = $menuName ?: $this->currentMenuName();
        $this->app->emptyActionsOfMenu($menuName);
    }

    /**
     * Initialise or re-initialise the menu actions to the $actions passed as argument.
     *
     * If no menu name is passed, the current menu name is used.
     *
     * @param array  $actions
     * @param string $menuName
     *
     * @return void
     */
    public function setMenuActions($actions, $menuName = '')
    {
        $menuName = $menuName ?: $this->currentMenuName();
        $this->app->setMenuActions($actions, $menuName);
    }

    /**
     * Add actions to the actions of a particualr menu.
     *
     * Any action that has the same index will be overwritten by the new action
     * in the actionBag. If the parameter replace is true, the old actions will
     * be rather completely replaced by the new actionBag.
     *
     * @param array  $actionBag
     * @param bool   $replace
     * @param string $menuName
     *
     * @return array The modified action bag
     */
    public function insertMenuActions($actionBag, $replace = false, $menuName = '')
    {
        $menuName = $menuName ?: $this->menuName();

        return $this->app->insertMenuActions($actionBag, $replace, $menuName);
    }

    /**
     * Empty, for this request, the actionBag of a particular menu.
     *
     * @param string $menuName
     *
     * @return void
     */
    public function emptyActionsOfMenu($menuName)
    {
        $this->app->emptyActionsOfMenu($menuName);
    }
}
