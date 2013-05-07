<?php

/**
 * @file
 * Process wrapper for rm:  Remove (unlink) the FILE(s).
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
 * Process wrapper for rm:  Remove (unlink) the FILE(s).
 */
class Rm extends Process
{
    /**
     * @name Setup Phase
     * @{
     */

    /**
     * Constructs the object, optionally setting the command to be executed.
     */
    function __construct($files = null)
    {
        assert(is_string($files) || is_array($files) || !isset($files));
        parent::__construct('rm');
        // set default options
        $this->_params['-fv'] = '-fv';
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
     * Remove directories recursive.
     * @return Rm
     */
    function recursive()
    {
        $this->_params['-r'] = '-r';
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
     * Get the list of files
     * @return array list of found files
     */
    function getFiles()
    {
        $this->exec();
        // return null if not successfull
        if (!$this->isSuccess())
            return null;
        // return simple lists
        return explode(PHP_EOL, rtrim($this->getOutput()));
    }

    /**
     * @}
     */

}


