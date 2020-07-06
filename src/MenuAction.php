<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice;

/**
 * Defines the actions of a particular menu.
 */
class MenuAction
{
    public function __construct(MenuEntity $menu)
    {
        $this->menu = $menu;
    }

    public function add(array $action)
    {
        $this->actions = array_replace($this->actions, $action);
    }

    public function addBackAction($option = '', $display = '')
    {
        if ($option && $display) {
            $this->add($this->menu->backAction($option, $display));
        } elseif ($option) {
            $this->add($this->menu->backAction($option));
        } else {
            $this->add($this->menu->backAction());
        }
    }
}
