<?php

namespace Rejoice\Console;

use Symfony\Component\Console\Input\InputOption;

class Option extends InputOption
{
    const IS_ARRAY = InputOption::VALUE_IS_ARRAY;

    const NONE = InputOption::VALUE_NONE;

    const OPTIONAL = InputOption::VALUE_OPTIONAL;

    const REQUIRED = InputOption::VALUE_REQUIRED;
}
