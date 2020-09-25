<?php

namespace Rejoice\Console;

use Symfony\Component\Console\Helper\Table as ConsoleTable;

class Table extends ConsoleTable
{
    public function body(array $rows)
    {
        return $this->setRows($rows);
    }

    public static function drawLine()
    {
        return new TableDivider();
    }

    public function head(array $headers)
    {
        return $this->setHeaders($headers);
    }

    public function show()
    {
        return $this->render();
    }

    public function border($style)
    {
        $supported = [
            'default'    => 'default',
            'none'       => 'compact',
            'row'        => 'borderless',
            'all'        => 'box',
            'all-double' => 'box-double',
            'double-all' => 'box-double',
        ];

        $symfonyStyle = $supported[$style] ?? $style;

        return $this->setStyle($symfonyStyle);
    }

    public function headTitle($title)
    {
        return $this->setHeaderTitle($title);
    }

    public function footTitle($title)
    {
        return $this->setFooterTitle($title);
    }
}
