<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Menu;

use Rejoice\Foundation\Kernel;

require_once __DIR__.'/../../constants.php';

/**
 * Provides the appropriate menu sought by the request and some menus related functions.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Menus implements \ArrayAccess
{
    /**
     * Instance of the kernel.
     *
     * @var Kernel
     */
    protected $app;

    /**
     * The menus retrieved from the menus.php|json file.
     *
     * @var array
     */
    protected $menus = [];

    /**
     * Path of the menus.php file.
     *
     * @var string
     */
    protected $menusPhp = '';

    /**
     * Path of the menus.json file.
     *
     * @var string
     */
    protected $menusJson = '';

    /**
     * Maximum numbers of characters that can be displayed on a USSD screen.
     *
     * @var int
     */
    protected $maxPageCharacters = 147;

    /**
     * Maximum new lines that can be displayed on a USSD screen.
     *
     * @var int
     */
    protected $maxPageLines = 10;

    public function __construct(Kernel $app)
    {
        $this->app = $app;
        $this->session = $app->session();
        $this->menusPhp = $this->menuPath().'/menus.php';
        $this->menusJson = $this->menuPath().'/menus.json';
        $this->hydrateMenus($app);
        $this->maxPageCharacters = $app->config(
            "channel.{$app->channel()}.max_page_characters"
        ) ?: $this->maxPageCharacters;
        $this->maxPageLines = $app->config(
            "channel.{$app->channel()}.max_page_lines"
        ) ?: $this->maxPageLines;
    }

    public function menuPath()
    {
        return $this->app->path('menu_resource_dir').'/';
    }

    public function hydrateMenus($app)
    {
        $this->menus = $this->retrieveMenus();

        if ($this->session->hasMetadata(CURRENT_MENU_ACTIONS)) {
            $modifications = $this->session->metadata(CURRENT_MENU_ACTIONS)[ACTIONS];
            $this->insertMenuActions($modifications, $app->currentMenuName());
        }

        if ($app->config('app.ask_user_before_reload_last_session')) {
            $this->menus = array_merge(
                $this->menus,
                $this->menuAskUserBeforeReloadLastSession()
            );
        }
    }

    public function menuAskUserBeforeReloadLastSession()
    {
        $message = $this->app->config('menu.message_ask_user_before_reload_last_session');
        $lastSessionTrigger = $this->app->config('menu.last_session_trigger');
        $lastSessionDisplay = $this->app->config('menu.last_session_display');
        $restartSessionTrigger = $this->app->config('menu.restart_session_trigger');
        $restartSessionDisplay = $this->app->config('menu.restart_session_display');

        return [
            ASK_USER_BEFORE_RELOAD_LAST_SESSION => [
                'message' => $message,
                'actions' => [
                    $lastSessionTrigger    => [
                        ITEM_MSG    => $lastSessionDisplay,
                        ITEM_ACTION => APP_CONTINUE_LAST_SESSION,
                    ],
                    $restartSessionTrigger => [
                        ITEM_MSG    => $restartSessionDisplay,
                        ITEM_ACTION => APP_WELCOME,
                    ],
                ],
            ],
        ];
    }

    public function modifyMenus($modifications)
    {
        foreach ($modifications as $menuName => $modif) {
            if (isset($modif[MSG])) {
                $this->menus[$menuName][MSG] = $modif[MSG];
            }

            if (isset($modif[ACTIONS])) {
                $this->insertMenuActions($modif[ACTIONS], $menuName);
            }
        }
    }

    /**
     * Insert an action to the action bag.
     *
     * Return the modified action bag
     *
     * @param  array   $actions
     * @param  string  $menuName
     * @param  bool $replace
     * @return array
     */
    public function insertMenuActions($actions, $menuName, $replace = false)
    {
        if (! isset($this->menus[$menuName][ACTIONS])) {
            $this->menus[$menuName][ACTIONS] = [];
        }

        if ($replace) {
            $this->menus[$menuName][ACTIONS] = $actions;
        } else {
            foreach ($actions as $key => $value) {
                $this->menus[$menuName][ACTIONS][$key] = $value;
            }
        }

        return $this->menus[$menuName][ACTIONS];
    }

    public function setMenuActions($actions, $menuName)
    {
        $this->emptyActionsOfMenu($menuName);

        return $this->insertMenuActions($actions, $menuName, true);
    }

    public function emptyActionsOfMenu($menuName)
    {
        $this->menus[$menuName][ACTIONS] = [];
    }

    public function retrieveMenus()
    {
        if (file_exists($this->menusPhp)) {
            return require $this->menusPhp;
        } elseif (file_exists($this->menusJson)) {
            return json_decode(
                file_get_contents($this->menusJson),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } elseif (class_exists($this->app->menuEntityClass('welcome'))) {
            return [];
        } else {
            throw new \Exception('Unable to found the Menus, neither in "'.$this->menusPhp.'", nor in "'.$this->menusJson.'" nor by looking for a welcome menu class the Menu entities folder ('.$this->app->menuEntityClass('welcome').').');
        }
    }

    public function readMenusResource()
    {
        $directory = new \RecursiveDirectoryIterator(
            $this->menuPath(),
            \FilesystemIterator::FOLLOW_SYMLINKS
        );

        $filter = new \RecursiveCallbackFilterIterator(
            $directory,
            function ($current, $key, $iterator) {
                $filename = $current->getFilename();
                if ('.' === $filename[0]) {
                    return false;
                }

                return in_array($filename, ['menus.php', 'menus.json']);
            }
        );

        $iterator = new \RecursiveIteratorIterator($filter);
        $files = [];
        $cutFrom = strlen($this->menuPath());
        foreach ($iterator as $info) {
            $file = $info->getPathname();
            $length = strpos($file, 'menus.') - $cutFrom;
            $menuName = substr($file, $cutFrom, $length);
            $files[] = str_replace(['/', '\\'], '.', $menuName);
        }

        return $files;
    }

    public function getNextMenuName(
        $userResponse,
        $menuName,
        $userResponseExistsInMenuActions
    ) {
        if (! empty($forcedFlow = $this->session->metadata(FORCED_MENU_FLOW, []))) {
            $nextMenu = array_shift($forcedFlow);
            $this->session->setMetadata(FORCED_MENU_FLOW, $forcedFlow);

            return $nextMenu;
        }

        if ($userResponseExistsInMenuActions) {
            if (is_array($this->menus[$menuName][ACTIONS][$userResponse][ITEM_ACTION])) {
                return $this->menus[$menuName][ACTIONS][$userResponse][ITEM_ACTION][MENU];
            }

            if (is_string($this->menus[$menuName][ACTIONS][$userResponse][ITEM_ACTION])) {
                return $this->menus[$menuName][ACTIONS][$userResponse][ITEM_ACTION];
            }

            throw new \Exception('Next menu name for option "'.$userResponse.'" in the menu "'.$menuName.'" must be an array or a string.');
        }

        if (
            $this->session->metadata('currentMenuSplitted', null) &&
            ! $this->session->metadata('currentMenuSplitEnd', null) && $this->app->config('menu.splitted_menu_next_trigger') === $userResponse) {
            return APP_SPLITTED_MENU_NEXT;
        }

        if (
            $this->session->metadata('currentMenuSplitted', null) &&
            ! $this->session->metadata('currentMenuSplitStart', null) && $this->app->config('menu.back_action_trigger') === $userResponse) {
            return APP_BACK;
        }

        if (isset($this->menus[$menuName][DEFAULT_NEXT_MENU])) {
            if (is_array($this->menus[$menuName][DEFAULT_NEXT_MENU])) {
                return $this->menus[$menuName][DEFAULT_NEXT_MENU][MENU];
            }

            if (is_string($this->menus[$menuName][DEFAULT_NEXT_MENU])) {
                return $this->menus[$menuName][DEFAULT_NEXT_MENU];
            }

            throw new \Exception('Default next menu name for the menu "'.$menuName.'" must be an array or a string.');
        }

        if ($nextMenu = $this->app->getDefaultNextMenuFromMenuEntity()) {
            if (is_array($nextMenu)) {
                return $nextMenu[MENU];
            }

            return $nextMenu;
        }

        return false;
    }

    public function getForcedFlowIfExists($menuName, $response)
    {
        if (isset($this->menus[$menuName][ACTIONS][$response][ITEM_LATER])) {
            return $this->menus[$menuName][ACTIONS][$response][ITEM_LATER];
        }

        if (
            isset($this->menus[$menuName][ACTIONS][$response][ITEM_ACTION]) &&
            is_array($this->menus[$menuName][ACTIONS][$response][ITEM_ACTION]) &&
            isset($this->menus[$menuName][ACTIONS][$response][ITEM_ACTION][ITEM_LATER])
        ) {
            return $this->menus[$menuName][ACTIONS][$response][ITEM_ACTION][ITEM_LATER];
        }

        if (isset($this->menus[$menuName][ITEM_LATER])) {
            return $this->menus[$menuName][ITEM_LATER];
        }

        if (
            isset($this->menus[$menuName][DEFAULT_NEXT_MENU]) &&
            is_array($this->menus[$menuName][DEFAULT_NEXT_MENU]) &&
            isset($this->menus[$menuName][DEFAULT_NEXT_MENU][ITEM_LATER])
        ) {
            return $this->menus[$menuName][DEFAULT_NEXT_MENU][ITEM_LATER];
        }

        if ($nextMenu = $this->app->getDefaultNextMenuFromMenuEntity()) {
            if (is_array($nextMenu)) {
                return $nextMenu[ITEM_LATER];
            }
        }

        return null;
    }

    public function saveForcedFlow($actionLater)
    {
        $type = gettype($actionLater);
        if (! in_array($type, ['array', 'string'])) {
            throw new \Exception('The parameter '.ITEM_LATER.' must be of  a the name or an array of the name(s) of the menu(s) you want to redirect to.');
        }

        $actionLater = is_array($actionLater) ? $actionLater : [$actionLater];
        $this->session->setMetadata(FORCED_MENU_FLOW, $actionLater);
    }

    public function menuStateExists($menuName)
    {
        return '' !== $menuName && (
            isset($this->menus[$menuName]) ||
            in_array($menuName, RESERVED_MENU_IDs, true) ||
            class_exists($this->app->menuEntityClass($menuName))
        );
    }

    public function splittedMenu_NextPageActionDisplay()
    {
        // Will return a string like: '00. More'
        return
            $this->app->config('menu.splitted_menu_next_trigger').
            $this->app->config('menu.trigger_decorator').
            $this->app->config('menu.splitted_menu_display');
    }

    public function splittedMenu_BackActionDisplay()
    {
        // A string like: '0. Back'
        return
            $this->app->config('menu.back_action_trigger').
            $this->app->config('menu.trigger_decorator').
            $this->app->config('menu.back_action_display');
    }

    public function getSplitMenuStringNext()
    {
        $index = $this->session->metadata('currentMenuSplitIndex') + 1;

        return $this->getSplitMenuStringAt($index);
    }

    public function getSplitMenuStringBack()
    {
        $index = $this->session->metadata('currentMenuSplitIndex') - 1;

        return $this->getSplitMenuStringAt($index);
    }

    public function getSplitMenuStringAt($index)
    {
        if ($index < 0) {
            throw new \Exception('Error: Splitted menu does not have page back page. This might not normally happen! Review the code.');
        } elseif (! isset($this->session->data['currentMenuChunks'][$index])) {
            throw new \Exception('Splitted menu does not have any next page.');
        }

        $this->updateSplittedMenuState($index);

        return $this->session->data['currentMenuChunks'][$index];
    }

    public function updateSplittedMenuState($index)
    {
        $end = count($this->session->data['currentMenuChunks']) - 1;

        switch ($index) {
            case 0:
                $this->session->data['currentMenuSplitStart'] = true;
                $this->session->data['currentMenuSplitEnd'] = false;
                break;

            case $end:
                $this->session->data['currentMenuSplitStart'] = false;
                $this->session->data['currentMenuSplitEnd'] = true;
                break;

            default:
                $this->session->data['currentMenuSplitStart'] = false;
                $this->session->data['currentMenuSplitEnd'] = false;
                break;
        }

        $this->session->data['currentMenuSplitIndex'] = $index;
    }

    public function getMenuString(
        $menuActions,
        $menuMsg = '',
        $hasBackAction = false
    ) {
        $menuString = $this->menuToString($menuActions, $menuMsg);

        if ($this->app->isUssdChannel()) {
            return $this->softPaginateIfOverflows($menuString, $hasBackAction);
        }

        return $menuString;
    }

    public function softPaginateIfOverflows($menuString, $hasBackAction)
    {
        if ($this->willOverflowWith($menuString)) {
            $chunks = explode("\n", $menuString);
            $menuChunks = $this->generateSplittedMenu($chunks, $hasBackAction);
            $menuString = $menuChunks[0];

            $this->saveMenuSplittedState($menuChunks, $hasBackAction);
        } else {
            $this->unsetPreviousSplittedMenuIfExists();
        }

        return $menuString;
    }

    public function willOverflowWith($message, $includeEdge = false)
    {
        if ($includeEdge) {
            return
                $this->app->isUssdChannel() &&
                (strlen($message) >= $this->maxPageCharacters() ||
                    count(explode("\n", $message)) >= $this->maxPageLines());
        } else {
            return
                $this->app->isUssdChannel() &&
                (strlen($message) > $this->maxPageCharacters() ||
                    count(explode("\n", $message)) > $this->maxPageLines());
        }
    }

    public function generateSplittedMenu($menuStringChunks, $hasBackAction)
    {
        $menuChunks = [];

        $first = 0;
        $last = count($menuStringChunks) - 1;

        $currentStringWithoutSplitMenu = '';

        $splittedMenuNext = $this->splittedMenu_NextPageActionDisplay();
        $splittedMenuBack = $this->splittedMenu_BackActionDisplay();

        foreach ($menuStringChunks as $menuItemNumber => $menuItemStr) {
            $splitMenu = '';

            // Add Goto next page action
            if ($menuItemNumber === $first || ! isset($menuChunks[0])) {
                $splitMenu = $splittedMenuNext;

                /*
                 * If the developer has implemented a back action (ie the
                 * developer wants the user to be able to go back the previous
                 * menu), we add a back action on the first page.
                 */
                if ($hasBackAction) {
                    $splitMenu .= "\n".$splittedMenuBack;
                }
            } elseif ($menuItemNumber === $last && ! $hasBackAction) {
                /*
                 * We add a back action at the end only if the developer has
                 * not already added a back action. We `assume` (and advice) the
                 * developer to make their back action the last action for this
                 * to display properly
                 */
                $splitMenu = $splittedMenuBack;
            } elseif ($menuItemNumber !== $last) {
                // If in the middle, add Next Page action and Back action
                $splitMenu = $splittedMenuNext."\n".$splittedMenuBack;
            }

            $newLine = $menuItemStr;
            $newLineWithSplitMenu = $menuItemStr."\n".$splitMenu;

            if ($this->willOverflowWith($newLineWithSplitMenu)) {
                $max = $this->maxPageCharacters() - strlen("\n".$splittedMenuNext."\n".$splittedMenuBack);
                $this->app->fail('The text <br>```<br>'.$menuItemStr.'<br>```<br><br> is too large to be displayed. Consider breaking it in pieces with the newline character (\n). Each piece must not exceed '.$max.' characters.');
            }

            /*
             * The order is important here. (setting
             * currentStringWithSplitMenu before
             * currentStringWithoutSplitMenu)
             */
            $currentStringWithSplitMenu = $currentStringWithoutSplitMenu."\n".$newLineWithSplitMenu;

            $currentStringWithoutSplitMenu .= "\n".$newLine;

            $next = $menuItemNumber + 1;
            $nextStringWithSplitMenu = '';

            if ($next < $last) {
                $nextLine = "\n".$menuStringChunks[$next];

                if (! isset($menuChunks[0])) {
                    $splitMenu = "\n".$splittedMenuNext;
                } else {
                    $splitMenu = "\n".$splittedMenuNext."\n".$splittedMenuBack;
                }

                $nextStringWithSplitMenu = $currentStringWithoutSplitMenu.$nextLine.$splitMenu;
            } else {
                $nextLine = "\n".$menuStringChunks[$last];
                $splitMenu = $hasBackAction ? '' : "\n".$splittedMenuBack;

                $nextStringWithSplitMenu = $currentStringWithoutSplitMenu.$nextLine.$splitMenu;
            }

            if (
                $this->willOverflowWith($nextStringWithSplitMenu, true) ||
                $menuItemNumber === $last
            ) {
                $menuChunks[] = trim($currentStringWithSplitMenu);
                $currentStringWithSplitMenu = '';
                $currentStringWithoutSplitMenu = '';
            }
        }

        return $menuChunks;
    }

    /**
     * Check if the menu will be the last to show to the user.
     *
     * Any menu that does not have actions will be the last to show to the user
     * (no actions means the developer is no more waiting for any response, so
     * the user can no more input something)
     *
     * @param  array     $actions
     * @return bool
     */
    public function isLastPage($menuName, $actions = [], $menuEntity = null)
    {
        return
            empty($actions)
            && ! isset($this->menus[$menuName][DEFAULT_NEXT_MENU])
            && (! $menuEntity
                || ($menuEntity
                    && ! method_exists($menuEntity, MENU_ENTITY_DEFAULT_NEXT_MENU)
                )
            );
    }

    public function saveMenuSplittedState($menuChunks, $hasBackAction)
    {
        $this->session->data['currentMenuSplitted'] = true;
        $this->session->data['currentMenuSplitIndex'] = 0;
        $this->session->data['currentMenuSplitStart'] = true;
        $this->session->data['currentMenuSplitEnd'] = false;
        $this->session->data['currentMenuChunks'] = $menuChunks;
        $this->session->data['currentMenuHasBackAction'] = $hasBackAction;
    }

    public function unsetPreviousSplittedMenuIfExists()
    {
        if (isset($this->session->data['currentMenuSplitted'])) {
            $this->session->data['currentMenuSplitted'] = false;

            $toDelete = [
                'currentMenuSplitIndex',
                'currentMenuSplitStart',
                'currentMenuSplitEnd',
                'currentMenuChunks',
                'currentMenuHasBackAction',
            ];

            foreach ($toDelete as $value) {
                unset($this->session->data[$value]);
            }
        }
    }

    public function menuToString($menuActions, $menuMsg = '')
    {
        $sep = $this->app->config('menu.seperator_message_and_actions', "\n\n");
        $triggerDecorator = $this->app->config('trigger_decorator', '. ');
        $menuString = $menuMsg.$sep;

        foreach ($menuActions as $trigger => $display) {
            $menuString .= $trigger.$triggerDecorator.$display."\n";
        }

        return trim($menuString);
    }

    public function get($id)
    {
        if (! isset($this->menus[$id])) {
            throw new \Exception('Menu "'.$id.' not found inside the menus.');
        }

        return $this->menus[$id];
    }

    public function has($id)
    {
        return isset($this->menus[$id]) ||
            class_exists($this->app->menuEntityClass($id));
    }

    public function getAll()
    {
        return $this->menus;
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
    public function maxPageCharacters()
    {
        return $this->maxPageCharacters;
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
    public function maxPageLines()
    {
        return $this->maxPageLines;
    }

    // ArrayAccess Interface
    public function offsetExists($offset)
    {
        return isset($this->menus[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->menus[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->menus[] = $value;
        } else {
            $this->menus[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->menus[$offset]);
    }
}
