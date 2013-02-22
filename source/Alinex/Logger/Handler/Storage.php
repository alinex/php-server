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

namespace Alinex\Logger;

use Alinex\Storage\Engine;

/**
 * Handler storing logs in given storage engine.
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
    }
    
    /**
     * Write the log message down.
     * @param mixed $format formatted log message
     */
    protected function write($formatted)
    {
        $key = time();
        $this->_engine->set($key, $formatted);
    }
}