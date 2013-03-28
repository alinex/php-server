<?php
/**
 * Short example showing the use of sessions without engine
 */

use Alinex\Dictionary\Session;

// get the session instance like already configured
// or like defined in registry
$session = Session::getInstance();

// start session handling
$session->start();

// set and get session attributes
$session->set('name', 'Alex');
$session->get('name');
