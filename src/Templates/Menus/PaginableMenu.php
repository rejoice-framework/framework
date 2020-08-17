<?php
$namespace = $namespace ?? 'App\Menus';
$namespace = trim($namespace, '\\');

$name = $name ?? 'PaginableMenu';

// When the name of the menu is the same as one of the imported classes
$baseMenu = $baseMenuNamespaceLast = 'Menu';
if ($name === $baseMenu) {
    $baseMenu = 'BaseMenu';
    $baseMenuNamespaceLast .= " as $baseMenu";
}

$paginator = $paginatorNamespaceLast = 'Paginator';
if ($name === $paginator) {
    $paginator = 'PaginatorTrait';
    $paginatorNamespaceLast .= " as $paginator";
}

$template = "<?php
namespace {$namespace};

use App\Menus\\$baseMenuNamespaceLast;
use Rejoice\Menu\\$paginatorNamespaceLast;

class $name extends $baseMenu
{
    use $paginator;

    protected \$maxItemsPerPage = 4;
    protected \$itemsTable = 'items_table_name';

    public function message()
    {
        if (!\$this->paginationTotal()) {
            return \"No item found\";
        }

        return 'Select an option';
    }

    /**
     * Will be called for each retrieved action
     *
     * You just need to customize the display and save_as parameters.
     *
     * @param array $row
     * @param string $actionTrigger
     * @return array
     */
    public function itemAction(\$row, \$actionTrigger)
    {
        return [
            \$actionTrigger => [
                'display' => \$row['name'],
                'next_menu' => 'next_menu_name',
                'save_as' => \$row['id'],
            ],
        ];
    }

    /**
     * Used by Rejoice to fetch the item to show on the current screen
     *
     * You just need to customize the `custom_conditions` and their parameters
     * bindings according to what you want to retrieve.
     *
     * @return array The fetched items
     */
    public function paginationFetch()
    {
        // Customize the item table name and the conditions in the query
        \$req = \$this->db()->prepare(
            \"SELECT *
            FROM `{\$this->itemsTable}`
            WHERE id > :offset
                AND {custom_condition_1}
                AND {custom_condition_2}
                AND {custom_condition_3}
            ORDER BY id
            LIMIT :limit\"
        );

        // Bind your custom conditions values if necessary
        // \$req->bindParam('custom_condition_1', '' );
        // \$req->bindParam('custom_condition_2', '');
        // \$req->bindParam('custom_condition_3', '');

        // This is managed by Rejoice and remains the same
        \$this->bindPaginationParams(\$req);
        \$req->execute();

        \$items = \$req->fetchAll(\PDO::FETCH_ASSOC);
        \$req->closeCursor();

        return \$items;
    }

    /**
     * The total number of rows that will be fetched.
     * Used to calculate the number of screens needed
     *
     * @return int
     */
    public function paginationCountAll()
    {
        /*
         * The table name and the query conditions here have to be the same as
         * the custom conditions in the paginationFetch method
         */
        \$req = \$this->db()->prepare(
            \"SELECT COUNT(*)
            FROM `{\$this->itemsTable}`
            WHERE {custom_condition_1}
                AND {custom_condition_2}
                AND {custom_condition_3}\"
        );

        // Bind your custom conditions values if necessary
        // \$req->bindParam('custom_condition_1', '');
        // \$req->bindParam('custom_condition_2', '');
        // \$req->bindParam('custom_condition_3', '');

        \$total = intval(\$req->fetchColumn());
        \$req->closeCursor();

        return \$total;
    }
}
";

return $template;
