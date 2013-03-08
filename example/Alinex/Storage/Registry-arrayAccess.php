<?php
/**
 * Short example showing how to use the default registry.
 */

use Alinex\Storage\Registry;

// create registry instance
$registry = Registry::getInstance();

// set the timezone
$registry['timezone'] = 'GMT+1';

// check if timezone is set
if (isset($registry['timezone'])) {
    // do something
    echo $registry['timezone'];
}

// remove timezone from registry
unset($registry['timezone']);

?>
