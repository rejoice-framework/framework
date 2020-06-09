<?php
/**
 * Implements methods to easily access all the user's responses
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 * @license MIT
 */

namespace Prinx\Rejoice;

class UserResponse implements \ArrayAccess
{
    public function __construct($responses)
    {
        $this->responses = $responses;
    }

    public function get($menu_name, $silent = false, $index = -1)
    {
        if (!isset($this->responses[$menu_name])) {
            if (!$silent) {
                return null;
            }

            throw new \Exception('No user response for the menu ' . $menu_name);
        }

        $len = count($this->responses[$menu_name]);
        $index = $index === -1 ? $len - 1 : $index;

        if (!isset($this->responses[$menu_name][$index])) {
            if (!$silent) {
                return null;
            }

            throw new \Exception('No user response at the index ' . $index);
        }

        return $this->responses[$menu_name][$index];
    }

    public function getAll($menu_name)
    {
        if (!isset($this->responses[$menu_name])) {
            throw new \Exception('No user response for the menu ' . $menu_name);
        }

        return $this->responses[$menu_name];
    }

    public function has($menu_name, $index = -1)
    {
        if (
            !isset($this->responses[$menu_name]) ||
            count($this->responses[$menu_name]) <= 0
        ) {
            return false;
        }

        $len = count($this->responses[$menu_name]);
        $index = $index === -1 ? $len - 1 : $index;

        return isset($this->responses[$menu_name][$index]);
    }

    // ArrayAccess Interface
    public function offsetExists($menu_name)
    {
        return isset($this->responses[$menu_name]);
    }

    public function offsetGet($menu_name)
    {
        return $this->getAll($menu_name);
    }

    public function offsetSet($menu_name, $value)
    {
        if (!is_array($value)) {
            throw new \Exception('User response must be contain an array');
        }

        if (is_null($menu_name)) {
            throw new \Exception('Cannot set a user response without the corresponding menu_name as index!');
        } else {
            $this->responses[$menu_name] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->responses[$offset]);
    }
}
