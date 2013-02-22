<?php
/**
 * @file
 * Handler storing logs in given storage engine.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

use Alinex\Logger\Handler;
use Alinex\Storage\Engine;

/**
 * Handler storing logs in given storage engine.
 * 
 * Each log message will be added under the unix timestamp with milliseconds.
 */
class Storage extends Handler
{
    /**
     * Storage engine to use.
     * @var \Alinex\Storage\Engine
     */
    private $_engine = null;
    
    /**
     * Set storage engine to use.
     * @param \Alinex\Storage\Engine $engine storage engine
     */
    function __construct(Engine $engine) 
    {
        $this->_engine = $engine;
        $this->_formatter = new \Alinex\Logger\Formatter\Object();
    }
    
    /**
     * Write the log message down.
     * @param mixed $format formatted log message
     */
    protected function write($formatted)
    {
        $this->_engine->set(microtime(true), $formatted);
    }
}