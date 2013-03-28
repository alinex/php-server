<?php
/**
 * Short example showing how to use autoloading from file
 */

use Alinex\Dictionary\Registry;

// autoload registry from file if not setup
// called anythere in init script
Registry::getInstance(
    'file://'.$dir.'registryData.ini',
    'file://'.$dir.'registryValidators.ini'
);

// ... later in code...

// get the already set registry
Registry::getInstance();

// use registry settings
if ($registry->has('database.host')) {
    // do something
    $host = $registry->get('database.host');
    // work with it
}
