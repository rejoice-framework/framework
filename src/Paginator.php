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
 * Implements all the logic for handling USSD Pagination
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
trait Paginator
{
    /*
     * Wonder if this class has to be a trait.
     * Tried an abstract class but it also resulted in some change and
     * complication of design at the user side.
     * Maybe the whole design has to be revisited.
     */

    /**
     * Fetches the items from the database
     *
     * @return array
     */
    abstract public function paginationFetch();

    /**
     * Returns the total number of the data to be displayed
     *
     * @return  int
     */
    abstract public function paginationTotal();

    /**
     * Defines how the items will be displayed to the user.
     *
     * This method will automatically be called for each rows of the array
     * returned by `paginationFetch`. And its return value will be added to the
     * actions
     *
     * The option is what will be displayed to the user as option to select.
     * It's automatically handled by the Paginator
     *
     * @param array $row
     * @param string $option
     * @return array
     */
    abstract public function paginationInsertAction($row, $option);

    public function before()
    {
        $previoulyRetrieved = $this->paginationGet('previously_retrieved');
        $previoulyRetrieved += count($this->paginationFetch());
        $this->paginationSave('previously_retrieved', $previoulyRetrieved);
        // $this->paginate();
    }

    public function paginate($useBack = true)
    {
    }

    public function actions()
    {
        return $this->paginationCurrentActions();
    }

    public function paginationCurrentActions()
    {
        $actions = [];

        if ($data = $this->paginationFetch()) {
            $fetchedCount = count($data);
            $this->paginationSave('showed_on_current_page', $fetchedCount);

            if ($fetchedCount) {
                $lastRetrievedId = intval($data[$fetchedCount - 1]['id']);
                $this->saveLastRetrievedId($lastRetrievedId);
            }

            $option = $this->paginationGet('previously_retrieved') - $this->paginationGet('showed_on_current_page');

            foreach ($data as $row) {
                $action = $this->paginationInsertAction($row, ++$option);
                $actions = $this->mergeAction($actions, $action);
            }

            if (!$this->isPaginationLastPage()) {
                $forwardAction = parent::paginateForwardAction($this->forwardTrigger());
                $actions = $this->mergeAction($actions, $forwardAction);
            }
        }

        if ($this->useBack()) {
            $back = $this->backAction($this->backTrigger());
            $actions = $this->mergeAction($actions, $back);
        }

        return $actions;
    }

    /**
     * Check if the current screen is the first screen of the pagination
     *
     * @return boolean
     */
    public function isPaginationFirstPage()
    {
        return ($this->paginationGet('previously_retrieved') <=
            $this->paginationTotalToShowPerPage());
    }

    /**
     * Check if the current screen is the last screen of the pagination
     *
     * @return boolean
     */
    public function isPaginationLastPage()
    {
        // return $this->paginationTotal() <= $this->lastRetrievedId();
        return $this->paginationGet('previously_retrieved') >= $this->paginationTotal();
    }

    /**
     * Will run when the user is paginating forward
     *
     * @return void
     */
    public function onPaginateForward()
    {
        $this->setMenuActions([]);
    }

    /**
     * Will run when the user is paginating back
     *
     * @return void
     */
    public function onPaginateBack()
    {
        $this->setMenuActions([]);

        $ids = $this->paginationGet('last_retrieved_ids');

        /*
         * Yes, pop twice.
         * Pop the last ID and the ID before the last ID
         * on paginate back, the script will run agoan and retrieve the latter
         * as last ID
         */
        array_pop($ids);
        array_pop($ids);
        $ids = empty($ids) ? $ids : [0];
        $this->paginationSave('last_retrieved_ids', $ids);

        $previoulyRetrieved = $this->paginationGet('previously_retrieved');
        $previoulyRetrieved -= ($this->paginationTotalToShowPerPage() +
            $this->paginationGet('showed_on_current_page'));
        $previoulyRetrieved = $previoulyRetrieved > 0 ? $previoulyRetrieved : 0;
        $this->paginationSave('previously_retrieved', $previoulyRetrieved);
    }

    /**
     * Will run when the user is moving to a next menu (not a next page of the
     * pagination but rather a completely different next_menu - The next menu
     * to where leads the pagination items)
     *
     * @return void
     */
    public function onMoveToNextMenu()
    {
        $ids = $this->paginationGet('last_retrieved_ids');
        array_pop($ids);
        $this->paginationSave('last_retrieved_ids', $ids);

        $previoulyRetrieved = $this->paginationGet('previously_retrieved');
        $previoulyRetrieved -= $this->paginationGet('showed_on_current_page');
        $this->paginationSave('previously_retrieved', $previoulyRetrieved);
    }

    /**
     * Will run when the user is returning back
     *
     * @return void
     */
    public function onBack()
    {
        $this->onPaginateBack();
    }

    /**
     * The actual number of items showed on the current screen
     *
     * This cannot be greater than the `paginationTotalToShowPerPage`
     * This is actually handled automatically by the Paginator. It is just the
     * count of the number of items that have been retrieved on the particular
     * page of the pagination
     *
     * @return int
     */
    public function paginationTotalShowedOnCurrentPage()
    {
        // $totalShowed = $this->paginationGet('showed_on_current_page');
        // return $totalShowed ?: count($this->paginationFetch());

        if (!isset($this->paginationTotalShowedOnCurrentPage)) {
            $this->paginationTotalShowedOnCurrentPage = count($this->paginationFetch());
        }

        return $this->paginationTotalShowedOnCurrentPage;
    }

    /**
     * Get the id of the last fetched item - the next query to
     * the database, will begin at that index
     *
     * @return int
     */
    public function lastRetrievedId()
    {
        $ids = $this->paginationGet('last_retrieved_ids');
        return $ids ? $ids[count($ids) - 1] : 0;
    }

    /**
     * Save the id of last fetched item of the pagination
     *
     * @param int $lastId
     * @return void
     */
    public function saveLastRetrievedId($lastId)
    {
        $ids = $this->paginationGet('last_retrieved_ids');
        $ids[] = $lastId;
        $this->paginationSave('last_retrieved_ids', $ids);
    }

    /**
     * Saves pagination data for the current menu
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function paginationSave($key, $value)
    {
        $this->makePaginable();
        $pagination = $this->sessionGet('pagination');
        $pagination[$this->menuName()][$key] = $value;
        $this->sessionSave('pagination', $pagination);
    }

    /**
     * Get a pagination data
     *
     * @param string $key
     * @return mixed
     */
    public function paginationGet($key)
    {
        $this->makePaginable();
        return $this->sessionGet('pagination')[$this->menuName()][$key];
    }

    /**
     * Prepare the session to handle pagination data for the current menu
     *
     * @return void
     */
    public function makePaginable()
    {
        $newPaginationData = [
            $this->menuName() => [
                'last_retrieved_ids' => [0],
                'previously_retrieved' => null,
                'total' => null,
                'showed_on_current_page' => null,
            ],
        ];

        if (!$this->sessionHas('pagination')) {
            $this->sessionSave('pagination', $newPaginationData);
        } elseif (!isset($this->sessionGet('pagination')[$this->menuName()])) {
            $pagination = $this->sessionGet('pagination');
            $pagination = array_merge($pagination, $newPaginationData);
            $this->sessionSave('pagination', $pagination);
        }
    }

    /**
     * Determines the proper back action to use, according to where we are in
     * the current pagination
     *
     * @param string $option
     * @param string $display
     * @return array
     */
    public function backAction($option = '0', $display = 'Back')
    {
        if ($this->isPaginationFirstPage()) {
            return parent::backAction($option, $display);
        } else {
            return parent::paginateBackAction($option, $display);
        }
    }

    /**
     * The maximum number of items that can be showed on the pagination screen;
     * It's configured as protected property of the menu entity
     *
     * @return int
     */
    public function paginationTotalToShowPerPage()
    {
        return $this->paginationTotalToShowPerPage ?? 5;
    }

    /**
     * Defines if the user can move back for this particular string
     *
     * @return boolean
     */
    public function useBack()
    {
        return $this->useBack ?? true;
    }

    /**
     * Defines the option the user will select to move back
     *
     * @return string
     */
    public function backTrigger()
    {
        return $this->backTrigger ?? '0';
    }

    /**
     * Defines the option the user will select to move forward
     *
     * @return string
     */
    public function forwardTrigger()
    {
        return $this->forwardTrigger ?? '00';
    }
}
