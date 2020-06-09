<?php
/**
 * Provides the appropriate menu sought by the request and some menus related functions
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

namespace Prinx\Rejoice;

require_once 'constants.php';

class Menus implements \ArrayAccess
{
    protected $kernel;
    protected $menus = [];
    protected $menu_ask_user_before_reload_last_session = [
        ASK_USER_BEFORE_RELOAD_LAST_SESSION => [
            'message' => 'Do you want to continue from where you left?',
            'actions' => [
                '1' => [
                    ITEM_MSG => 'Continue last session',
                    ITEM_ACTION => APP_CONTINUE_LAST_SESSION,
                ],
                '2' => [
                    ITEM_MSG => 'Restart',
                    ITEM_ACTION => APP_WELCOME,
                ],
            ],
        ],
    ];

    protected $menus_php = '';
    protected $menus_json = '';

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
        $this->menus_php = $this->menuPath() . '/menus.php';
        $this->menus_json = $this->menuPath() . '/menus.json';
        $this->hydrateMenus($kernel);
    }

    public function menuPath()
    {
        return $this->kernel->config('menus_root_path') . '/' . $this->kernel->menusNamespace();
    }
    public function hydrateMenus($kernel)
    {
        $this->menus = $this->retrieveMenus();

        if (isset($kernel->sessionData()[MODIFY_MENUS])) {
            $modifications = $kernel->sessionData()[MODIFY_MENUS];

            $this->modifyMenus($modifications);
        }

        $this->menus = array_merge(
            $this->menus,
            $this->menu_ask_user_before_reload_last_session
        );
    }

    public function modifyMenus($modifications)
    {
        foreach ($modifications as $menu_name => $modif) {
            if (isset($modif[MSG])) {
                $this->menus[$menu_name][MSG] = $modif[MSG];
            }

            if (isset($modif[ACTIONS])) {
                $this->modifyPageActions($modif[ACTIONS], $menu_name);
            }
        }
    }

    public function modifyPageActions($modifications, $menu_name)
    {
        if (!isset($this->menus[$menu_name][ACTIONS])) {
            $this->menus[$menu_name][ACTIONS] = [];
        }

        foreach ($modifications as $key => $value) {
            $this->menus[$menu_name][ACTIONS][$key] = $value;
        }
    }

    public function retrieveMenus()
    {
        if (file_exists($this->menus_php)) {
            return require $this->menus_php;
        } elseif (file_exists($this->menus_json)) {
            return json_decode(
                file_get_contents($this->menus_json),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } else {
            throw new \Exception('Unable to found the Menus, neither in "' . $this->menus_php . '", nor in "' . $this->menus_json . '".');
        }
    }

    public function getNextMenuName(
        $user_response,
        $menu_name,
        $user_response_exists_in_menu_actions
    ) {
        if ($user_response_exists_in_menu_actions) {
            return $this->menus[$menu_name][ACTIONS][$user_response][ITEM_ACTION];

        } elseif (
            $this->sessionData('current_menu_splitted') &&
            !$this->sessionData('current_menu_split_end') &&
            $user_response === $this->kernel->appParams()['splitted_menu_next_thrower']
        ) {
            return APP_SPLITTED_MENU_NEXT;

        } elseif (
            $this->sessionData('current_menu_splitted') &&
            !$this->sessionData('current_menu_split_start') &&
            $user_response === $this->kernel->appParams()['back_action_thrower']
        ) {
            return APP_BACK;

        } elseif (isset($this->menus[$menu_name][ACTIONS][DEFAULT_MENU_ACTION])) {
            return $this->menus[$menu_name][ACTIONS][DEFAULT_MENU_ACTION];

        } else {
            return false;
        }
    }

    public function menuStateExists($id)
    {
        return $id !== '' &&
            (isset($this->menus[$id]) || in_array($id, RESERVED_MENU_IDs, true));
    }

    public function splittedMenuNextActionDisplay()
    {
        return $this->kernel->appParams()['splitted_menu_next_thrower'] . ". " .
        $this->kernel->appParams()['splitted_menu_display'];
    }

    public function splittedMenuBackActionDisplay()
    {
        return $this->kernel->appParams()['back_action_thrower'] . ". " .
        $this->kernel->appParams()['back_action_display'];
    }

    public function getSplitMenuStringNext()
    {
        $index = $this->kernel->session_data['current_menu_split_index'] + 1;
        return $this->getSplitMenuStringAt($index);
    }

    public function getSplitMenuStringBack()
    {
        $index = $this->kernel->session_data['current_menu_split_index'] - 1;
        return $this->getSplitMenuStringAt($index);
    }

    public function getSplitMenuStringAt($index)
    {
        if ($index < 0) {
            throw new \Exception('Error: Splitted menu does not have page back page. This might not normally happen! Review the code.');
        } elseif (!isset($this->kernel->session_data['current_menu_chunks'][$index])) {
            throw new \Exception('Splitted menu does not have any next page.');
        }

        $this->updateSplittedMenuState($index);

        return $this->kernel->session_data['current_menu_chunks'][$index];
    }

    public function updateSplittedMenuState($index)
    {
        $end = count($this->kernel->session_data['current_menu_chunks']) - 1;

        switch ($index) {
            case 0:
                $this->kernel->session_data['current_menu_split_start'] = true;
                $this->kernel->session_data['current_menu_split_end'] = false;
                break;

            case $end:
                $this->kernel->session_data['current_menu_split_start'] = false;
                $this->kernel->session_data['current_menu_split_end'] = true;
                break;

            default:
                $this->kernel->session_data['current_menu_split_start'] = false;
                $this->kernel->session_data['current_menu_split_end'] = false;
                break;
        }

        $this->kernel->session_data['current_menu_split_index'] = $index;
    }

    public function getMenuString(
        $menu_actions,
        $menu_msg = '',
        $has_back_action = false
    ) {
        $menu_string = $this->menuToString($menu_actions, $menu_msg);

        $chunks = explode("\n", $menu_string);
        $lines = count($chunks);

        if (
            strlen($menu_string) > $this->kernel->maxUssdPageContent() ||
            $lines > $this->kernel->maxUssdPageLines()
        ) {
            $menu_chunks = $this->splitMenu($chunks, $has_back_action);
            $menu_string = $menu_chunks[0];

            $this->saveMenuSplittedState($menu_chunks, $has_back_action);
        } else {
            $this->unsetPreviousSplittedMenuIfExists();
        }

        return $menu_string;
    }

    public function splitMenu($menu_string_chunks, $has_back_action)
    {
        $menu_chunks = [];

        $first = 0;
        $last = count($menu_string_chunks) - 1;

        $current_string_without_split_menu = '';

        $splitted_menu_next = $this->splittedMenuNextActionDisplay();
        $splitted_menu_back = $this->splittedMenuBackActionDisplay();

        foreach (
            $menu_string_chunks as $menu_item_number => $menu_item_str
        ) {
            $split_menu = '';

            if ($menu_item_number === $first || !isset($menu_chunks[0])) {
                $split_menu = $splitted_menu_next;

                if ($has_back_action) {
                    $split_menu .= "\n" . $splitted_menu_back;
                }
            } elseif ($menu_item_number === $last && !$has_back_action) {
                $split_menu = $splitted_menu_back;
            } elseif ($menu_item_number !== $last) {
                $split_menu = $splitted_menu_next . "\n" . $splitted_menu_back;
            }

            $new_line = $menu_item_str;
            $new_line_with_split_menu = $menu_item_str . "\n" . $split_menu;
            if (
                strlen($new_line_with_split_menu) > $this->kernel->maxUssdPageContent() ||
                count(explode("\n", $new_line_with_split_menu)) > $this->kernel->maxUssdPageLines()
            ) {
                $max = $this->kernel->maxUssdPageContent() - strlen("\n" . $splitted_menu_next . "\n" . $splitted_menu_back);
                exit('The text <br>```<br>' . $menu_item_str . '<br>```<br><br> is too large to be displayed. Consider breaking it in pieces with the newline character (\n). Each piece must not exceed ' . $max . ' characters.');

                // $exploded = str_split($menu_item_str, $max);
                // $menu_item_str = join("\n", $exploded);
            }

            /*
             * The order is important here. (setting
             * current_string_with_split_menu before
             * current_string_without_split_menu)
             */
            $current_string_with_split_menu = $current_string_without_split_menu . "\n" . $new_line_with_split_menu;

            $current_string_without_split_menu .= "\n" . $new_line;

            $next = $menu_item_number + 1;
            $next_string_with_split_menu = '';

            if ($next < $last) {
                $next_line = "\n" . $menu_string_chunks[$next];

                if (!isset($menu_chunks[0])) {
                    $split_menu = "\n" . $splitted_menu_next;
                } else {
                    $split_menu = "\n" . $splitted_menu_next . "\n" . $splitted_menu_back;
                }

                $next_string_with_split_menu = $current_string_without_split_menu . $next_line . $split_menu;
            } else {
                $next_line = "\n" . $menu_string_chunks[$last];
                $split_menu = $has_back_action ? '' : "\n" . $splitted_menu_back;

                $next_string_with_split_menu = $current_string_without_split_menu . $next_line . $split_menu;
            }

            if (
                strlen($next_string_with_split_menu) >= $this->kernel->maxUssdPageContent() ||
                count(explode("\n", $next_string_with_split_menu)) >= $this->kernel->maxUssdPageLines() ||
                $menu_item_number === $last
            ) {
                $menu_chunks[] = trim($current_string_with_split_menu);
                $current_string_with_split_menu = '';
                $current_string_without_split_menu = '';
            }
        }

        return $menu_chunks;
    }

    public function saveMenuSplittedState($menu_chunks, $has_back_action)
    {
        $this->kernel->session_data['current_menu_splitted'] = true;
        $this->kernel->session_data['current_menu_split_index'] = 0;
        $this->kernel->session_data['current_menu_split_start'] = true;
        $this->kernel->session_data['current_menu_split_end'] = false;

        $this->kernel->session_data['current_menu_chunks'] = $menu_chunks;
        $this->kernel->session_data['current_menu_has_back_action'] = $has_back_action;
    }

    public function unsetPreviousSplittedMenuIfExists()
    {
        if (isset($this->kernel->session_data['current_menu_splitted'])) {
            $this->kernel->session_data['current_menu_splitted'] = false;

            $to_delete = [
                'current_menu_split_index',
                'current_menu_split_start',
                'current_menu_split_end',
                'current_menu_chunks',
                'current_menu_has_back_action',
            ];

            foreach ($to_delete as $value) {
                unset($this->kernel->session_data[$value]);
            }
        }
    }

    public function menuToString($menu_actions, $menu_msg = '')
    {
        $menu_string = $menu_msg . "\n\n";

        foreach ($menu_actions as $menu_item_number => $menu_item_str) {
            $menu_string .= "$menu_item_number. $menu_item_str\n";
        }

        return trim($menu_string);
    }

    public function sessionData($id, $silent = true)
    {
        if ($id !== '') {
            if ($silent && !isset($this->kernel->session_data[$id])) {
                return null;
            }

            return $this->kernel->session_data[$id];
        }

        return $this->kernel->session_data;
    }

    public function sessionSave($id, $value)
    {
        $this->kernel->session_data[$id] = $value;
        return $this;
    }

    public function get($id)
    {
        if (!isset($this->menus[$id])) {
            throw new \Exception('Menu "' . $id . ' not found inside the menus.');
        }

        return $this->menus[$id];
    }

    public function has($id)
    {
        return isset($this->menus[$id]);
    }

    public function getAll()
    {
        return $this->menus;
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
