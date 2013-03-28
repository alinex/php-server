<?php
/**
 * Short example showing how to use the default registry.
 */

use Alinex\Dictionary\Registry;

// create registry instance
$registry = Registry::getInstance();

// set the timezone
$registry->set('timezone', 'GMT+1');

// set the database connection
$dbconfig = array(
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'alex',
    'password' => 'weissnicht',
    'database' => 'alinex'
);
$registry->groupSet('database.', $dbconfig);

// check if database is set
if ($registry->has('database.host')) {
    // do something
    $host = $registry->get('database.host');
    // work with it
}

// remove timezone from registry
$registry->remove('timezone');
