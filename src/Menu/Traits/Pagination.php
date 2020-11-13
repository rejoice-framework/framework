<?php

namespace Rejoice\Menu\Traits;

/**
 * Base menu methods relative to pagination.
 */
trait Pagination
{
    /**
     * Get a pagination session data.
     *
     * @param string $key
     * @param string $menu
     *
     * @return mixed
     */
    public function paginationGet(string $key, string $menu)
    {
        $menu = $menu ?: $this->menuName();

        return $this->sessionGet("pagination.{$menu}.{$key}");
    }
}
