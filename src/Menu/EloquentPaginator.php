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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Menu Paginator using the Eloquent ORM.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
trait EloquentPaginator
{
    use Paginator;

    /**
     * Pagination items.
     *
     * @var Illuminate\Database\Eloquent\Collection
     */
    protected $paginationItems;

    /**
     * Number of items to paginate.
     *
     * @var int
     */
    protected $paginationCountAll;

    /**
     * Eloquent Query builder to retrieve items to paginate.
     *
     * This is the query builder and not the actual items.
     * Therefore, you must not use '->get()' on the query builder to retrieve the items.
     * Rejoice will automatically do that when fetching the items for the specific page.
     *
     * @return Rejoice\Database\Model|Rejoice\Database\QueryBuilder|Illuminate\Support\Collection
     */
    abstract public function paginate();

    public function paginationCountAll()
    {
        if (is_null($this->paginationCountAll)) {
            $items = $this->paginate();

            if ($items instanceof Builder || $items instanceof Collection) {
                $this->paginationCountAll = $items->count();
            } elseif (is_array($items) || is_object($items)) {
                $this->paginationCountAll = collect($items)->count();
            } else {
                throw new \RuntimeException('The paginate method must return a query builder or an array of arrays or an array of objects');
            }
        }

        return $this->paginationCountAll;
    }

    public function paginationFetch()
    {
        if (is_null($this->paginationItems)) {
            $items = $this->paginate();

            if ($items instanceof Builder) {
                $condition = $this->paginationOffsetCondition();
                $limit = $this->perPage();
                $orderBy = $this->orderBy();
                $orderBy = is_array($orderBy) ? $orderBy : [$orderBy];

                $this->paginationItems = $items->where($condition)
                    ->limit($limit)
                    ->orderBy(...$orderBy)
                    ->get();
            } elseif ($items instanceof Collection) {
                $this->paginationItems = $items;
            } elseif (is_array($items) || is_object($items)) {
                $this->paginationItems = collect($items)->map(function ($item) {
                    return collect($item);
                });
            } else {
                throw new \RuntimeException('The paginate method must return a query builder or an array of arrays or an array of objects');
            }
        }

        $this->paginationSave('items', $this->paginationItems);

        return $this->paginationItems;
    }

    public function orderBy()
    {
        return ['id', 'asc'];
    }

    public function paginationOffsetColumn()
    {
        return 'id';
    }

    public function paginationOffset()
    {
        return $this->lastRetrievedId();
    }

    public function paginationOffsetCondition()
    {
        return [
            [$this->paginationOffsetColumn(), '>', $this->paginationOffset()],
        ];
    }
}
