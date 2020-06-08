<?php
require_once realpath(__DIR__) . '/../../../autoload.php';
require_once 'constants.php';

use Prinx\Rejoice\Database;
use Prinx\Utils\Date;
use Prinx\Utils\Str;

echo Str::internationaliseNumber('233 (54 54-66 796');
echo '<br>';

var_dump(preg_match('/^(\+|00)?[0-9-() ]{8,}$/', '00233 (54 54-66 796'));
echo '<br>';

var_dump(preg_match('/^[0-9]+(,[0-9]+)*\.?[0-9]*$/', '1.0000087909'));
echo '<br>';

function betStatuses()
{
    $db = Database::loadAppDBs()['default'];

    $req = $db->prepare("SELECT * FROM bet_statuses");
    $req->execute();

    $statuses = $req->fetchAll();
    echo 'fetchAll = ';
    var_dump($statuses);
    echo '<br>';
    $req->closeCursor();

    return $statuses;
}

function betStatusId($name)
{
    foreach (betStatuses() as $status) {
        if ($status['name'] === $name) {
            return intval($status['id']);
        }
    }

    throw new \Exception('Trying to get an unexistant bet status `' . $name . '`');
}

var_dump(betStatusId('BET_NEW'));
echo '<br>';

var_dump(explode(' ', 2));
echo '<br>';

function createAppNamespace($prefix = '')
{
    echo '<br>' . __FUNCTION__ . '<br>';

    $namespace = Str::pascalCase('default');

    $pos = strpos(
        $namespace,
        $prefix,
        strlen($namespace) - strlen($prefix)
    );

    $not_already_prefixed = $pos === -1 || $pos !== 0;

    if ($not_already_prefixed) {
        $namespace .= $prefix;
    }

    return $namespace;
}

var_dump(createAppNamespace(MENUS_NAMESPACE_PREFIX));

function hasPassed($date, $format = 'd/m/Y')
{
    echo '<br>' . __FUNCTION__ . '<br>';
    return DateTime::createFromFormat($format, $date) < DateTime::createFromFormat($format, date($format));
}

var_dump(hasPassed('7/06/2020'));

echo '<br>' . date('d/m/Y', Date::future(7));
