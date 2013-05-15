<?php

/**
 * @file
 * Process wrapper for ls: list directory contents
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
 * Process wrapper for ls: list directory contents
 *
 * List  information  about	 the FILEs (the current directory by default).
 * The default sort order will be by name.
 */
class Ls extends Process
{
    /**
     * @name Setup Phase
     * @{
     */

    /**
     * Constructs the object, optionally setting the command to be executed.
     * @param string|array $files directories or files to list
     */
    function __construct($files = null)
    {
        assert(is_string($files) || is_array($files) || !isset($files));
        parent::__construct('ls');
        // set default options
        $this->_params['-1'] = '-1';
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
     * Do not ignore entries starting with .
     * @return Ls
     */
    function showAll()
    {
        $this->_params['-a'] = '-a';
        return $this;
    }

    /**
     * Do not list implied . and ..
     * @return Ls
     */
    function showAlmostAll()
    {
        $this->_params['-A'] = '-A';
        return $this;
    }

    /**
     * Set the sort order.
     * This may be used in combination with setTime() to specify which type of
     * time to use.
     * @param string $type one of: extension, size, time, version
     * @return Ls
     */
    function setSort($type)
    {
        assert(is_string($type));
        assert(
            in_array(
                $type, array(
                    'extension', 'size', 'time', 'version',
                )
            )
        );

        if ($type == 'atime' || $type == 'ctime') {
            $this->_params['--time'] = '--sort='.$type;
        }
        $this->_params['--sort'] = '--sort='.$type;
        return $this;
    }

    /**
     * Set the time type.
     * This may be used in combination with setSort() to also sort after this
     * time.
     * @param string $type one of: atime, ctime
     * @return Ls
     */
    function setTime($type)
    {
        assert(is_string($type));
        assert(in_array($type, array('atime', 'ctime')));

        $this->_params['--time'] = '--time='.$type;
        return $this;
    }

    /**
     * Reverse sort order.
     * The type of sort is defined using setSort()
     * @return Ls
     */
    function sortReverse()
    {
        $this->_params['-r'] = '-r';
        return $this;
    }

    /**
     * Add trailing slash to directories
     * @return Ls
     */
    function addTrailingSlash()
    {
        $this->_params['-p'] = '-p';
        return $this;
    }

    /**
     * Flag defines if a long form of the output will be queried.
     * @var type
     */
    private $_longFormat = false;

    /**
     * List of column names to be used in result hash of long lists
     * @var type
     */
    private $_columns = array('file');

    /**
     * Add trailing slash to directories
     * This will get the following file informations:
     * - mode - file axxecc rights
     * - links'- number of links to this inode
     * - owner - user which owns this inode
     * - group - which owns this inode
     * - size - of file in byte
     * - date
     * - time
     * - file
     * @return Ls
     */
    function useLongFormat()
    {
        $this->_longFormat = true;
        $this->_columns =
            array('mode', 'links', 'owner', 'group', 'size', 'date', 'time', 'file');
        unset($this->_params['-1']);
        $this->_params['-l'] = '-l';
        $this->_params['--time-style'] = '--time-style=long-iso';
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
        $lines = explode(PHP_EOL, rtrim($this->getOutput()));
        if (!$this->_longFormat)
            return $lines;
        // return long list
        $result = array();
        if (count($lines > 1)) {
            array_shift($lines); // first line is heading
            foreach ($lines as $line) {
                $line = preg_replace('/ -> .*$/', '', $line);
                $cols = preg_split('/\s+/', $line, count($this->_columns));
                $entry = array();
                for ($i=0; $i<count($this->_columns); $i++)
                    $entry[$this->_columns[$i]] = $cols[$i];
                $result[] = $entry;
            }
        }
        return $result;
    }

    /**
     * @}
     */

}


