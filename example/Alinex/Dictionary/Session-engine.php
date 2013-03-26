<?php
/**
 * Using a storage engine in sessions
 */

use Alinex\Dictionary\Session;
use Alinex\Dictionary\Engine;

// get the session instance like already configured
// or like defined in registry
$session = Session::getInstance();
// set autodetect engine
$session->setEngine(Engine::getInstance('sess'));

// start session handling
$session->start();

// set and get session attributes
$session->set('name', 'Alex');
$session->get('name');
