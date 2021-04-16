<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Session;

/**
 * Provides an interface to respect by any created session driver.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
interface SessionInterface
{
    public function isPrevious();

    /**
     * Delete session data only from the storage.
     *
     * Does not delete the current live session data.
     *
     * @return void
     */
    public function delete();

    public function reset();

    /**
     * Reset completely the session data, both in live and in the storage.
     *
     * @return void
     */
    public function hardReset();
    
    /**
     * Attempts to retrieve a previous session data from the storage.
     *
     * @return void
     */
    public function retrievePreviousData();

    public function retrieveData();

    public function previousSessionNotExists();

    /**
     * Save the session data to the current configured storage.
     *
     * @return void
     */
    public function save();
}
