<?php
/**
 * Using the cache.
 */

use Alinex\Dictionary\Cache;

// get the cache instance like already configured
// or like defined in registry
$cache = Cache::getInstance();

// set and get session attributes
$cache->set('name', 'Alex');
$cache->get('name');

// cleanup old cache entries
$cache->gc();