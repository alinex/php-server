<?php

/**
 * @file
 * Process wrapper for touch: change file access and modification time.
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
 * Process wrapper for touch: change file access and modification time.
 *
 * By default the access and modification time is set to the current time.
 */
class Touch extends Process
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
        parent::__construct('touch');
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
     * Change only the access time.
     * @return Touch
     */
    function setOnlyAccessTime()
    {
        unset($this->_params['-m']);
        $this->_params['-a'] = '-a';
        return $this;
    }

    /**
     * Change only the modification time.
     * @return Touch
     */
    function setOnlyModificationTime()
    {
        unset($this->_params['-a']);
        $this->_params['-m'] = '-m';
        return $this;
    }

    /**
     * Do not create a file if not existing.
     * @return Touch
     */
    function noFileCreation()
    {
        $this->_params['-c'] = '-c';
        return $this;
    }

    /**
     * Copy the time from an existing file.
     * @param string $from copy timestamps from this file
     * @return Touch
     */
    function copyTimeFrom($file)
    {
        assert(is_string($file));

        $this->_params['-r'] = '-r '. escapeshellarg($file);
        return $this;
    }

    /**
     * Set the date to a specific value.
     * @param string $date value to set for file
     * @return Touch
     */
    function setDate($date)
    {
        assert(is_string($file));

        $this->_params['-t'] = '-t '. escapeshellarg($time);
        return $this;
    }

    /**
     * @}
     */

}


