<?php

/**
 * @file
 * Process wrapper for mkdir: make directories
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Proc\File;

use Alinex\Proc\Process;

/**
 * Process wrapper for mkdir: make directories
 */
class Mkdir extends Process
{
    /**
     * @name Setup Phase
     * @{
     */

    /**
     * Constructs the object, optionally setting the command to be executed.
     * @param string|array $files directories to create
     */
    function __construct($files = null)
    {
        assert(is_string($files) || is_array($files) || !isset($files));
        parent::__construct('mkdir');
        // set default options
        $this->_params['-v'] = '-v';
        // set given paths
        $this->_params['files'] = '';
        if (isset($files)) {
            if (is_string($files))
                $this->_params['files'] = escapeshellarg($files);
            else
                foreach($files as $file)
                    $this->_params['files'] .= ' '.escapeshellarg($file);
        }
    }

    /**
     * Make parent directories if needed, no error if existing
     * @return Mkdir
     */
    function createParents()
    {
        $this->_params['-p'] = '-p';
        return $this;
    }

    /**
     * @}
     */

    /**
     * @name Analyzation Phase
     * @{
     */

    /**
     * Get the list of directories created
     * @return array list of created directories
     */
    function getDirectories()
    {
        $this->exec();
        // return null if not successfull
        if (!$this->isSuccess())
            return null;
        // return simple lists
        return preg_replace(
            '/^.*?\`(.*)\'.*?$/', '$1',
            explode(PHP_EOL, rtrim($this->getOutput()))
        );
    }

    /**
     * @}
     */

}


