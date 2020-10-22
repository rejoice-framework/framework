<?php

namespace Rejoice\Console\Traits;

use Rejoice\Console\Table;
use Rejoice\Console\TableDivider;

/**
 * Console Table rendering related methods.
 * 
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
trait TableTrait
{
    public function createTable()
    {
        return new Table($this->getOutput());
    }

    public function tableLine()
    {
        return new TableDivider;
    }
}
