<?php

/**
 * @file
 * Process wrapper for mv: move (rename) files
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Process\File;

use Alinex\Process\Process;

/**
 * Process wrapper for mv: move (rename) files
 */
class Mv extends Process
{
    /**
     * @name Setup Phase
     * @{
     */

    /**
     * Constructs the object, optionally setting the command to be executed.
     * @param string|array $files list of files to move
     * @param string $dest destination file (one file) or directory (multiple)
     */
    function __construct($files, $dest)
    {
        assert(is_string($files) || is_array($files) || !isset($files));
        assert(is_string($dest));

        parent::__construct('mv');
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
        $this->_params['to'] = escapeshellarg($dest);
    }

    /**
     * Do not warn on already existing files
     * @return Mv
     */
    function overwrite()
    {
        $this->_params['-f'] = '-f';
        return $this;
    }

    /**
     * move only when the SOURCE file is newer than the destination
	 * file or when the destination file is missing
     * @return Mv
     */
    function update()
    {
        $this->_params['-u'] = '-u';
        return $this;
    }

    /**
     * Automatically create target directory if not existing
     * @return Mv
     */
    function createDir()
    {
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
        // extract values as array
        $result = array();
        $part = null;
        foreach(explode(PHP_EOL, rtrim($this->getOutput())) as $line)
            if (preg_match(
                "/^.*?\`(.*)\' -> \`(.*)\'.*?$/",
                $line, $part
            )) $result[] = array('from' => $part[1], 'to' => $part[2]);
        // return extracted values
        return $result;
    }

    /**
     * @}
     */

}


