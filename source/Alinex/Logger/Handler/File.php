<?php
/**
 * @file
 * Logging to stdout which may be the terminal or apache log.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

/**
 * Logging to stdout which may be the terminal or apache log.
 */
class File extends Stream
{
    /**
     * Initialize the stream.
     * 
     * @param string $file file reference
     * @param bool $keepOpened more performant but only possible for local
     * files, written by single process.
     */
    function __construct($file, $keepOpened = false)
    {
        // $file have to be writable
        assert(
            \Alinex\Validator\IO::path(
                $file, 'streamfile',
                array('writable' => true)
            )
        );
        assert(is_string($file));
        assert(is_bool($keepOpened));

        // set the flags
        $flags = 0;
        if (!$keepOpened)
            $flags |= Stream::FLAG_CLOSE;
        // init the stream
        parent::__construct($file, null, $flags);
    }
}