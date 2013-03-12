<?php
/**
 * @file
 * Import and export registry values in/to files.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\ImportExport;

use Alinex\Dictionary;
use Alinex\Util\String;

/**
 * Import and export registry values in/to files.
 */
abstract class File extends Dictionary\ImportExport
{
    /**
     * Prefix to be used in comment lines before the text.
     */
    const COMMENT_PREFIX = '';

    /**
     * Postfix used in comment lines behind the text.
     */
    const COMMENT_POSTFIX = '';

    /**
     * Create a new storage interface.
     *
     * @param Dictionary\Engine $data Dictionary class to import to or export from
     * @param string $file file to read from or write to
     */
    function __construct(Dictionary\Engine $data = null, $file = null)
    {
        assert(!isset($file) || is_string($file));

        parent::__construct($data);
        if (isset($file))
            $this->setFile($file);
    }

    /**
     * INI File to read from or write to.
     * @var string
     */
    protected $_file = null;

    /**
     * Set the file to read from or write to.
     * @param string $file
     */
    function setFile($file)
    {
        $this->_file = $file;
    }

    /**
     * Check that given file is readable.
     * @throws Exception if file is not readable
     */
    protected function checkReadable()
    {
        // read from file
        if (!is_readable($this->_file))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The file {file} is not readable',
                    array('file' => String::dump($this->_file))
                )
            );
    }

    /**
     * Check that given file is writable.
     * @throws Exception if file is not writable and can't be created
     */
    protected function checkWritable()
    {
        // write to file
        if (!is_writable($this->_file) && !is_writable(dirname($this->_file)))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The file {path} is not writable',
                    array('path' => String::dump($this->_file))
                )
            );
    }

    /**
     * Get the text as comment lines.
     * @param string $text text to be formated as comment
     * @return string lines of comment
     */
    protected function getComment($text)
    {
        $content = '';
        foreach (explode(PHP_EOL, String::wordbreak($text)) as $line)
            $content .= static::COMMENT_PREFIX
                .$line.static::COMMENT_POSTFIX.PHP_EOL;
        return $content;
    }

    /**
     * Get the file header comment.
     * @return string lines of comment
     */
    protected function getCommentHeader()
    {
        $content = '';
        // create header
        if (strlen($this->_header)) {
            $content .=  $this->getComment(str_repeat('-', 75));
            foreach (explode(PHP_EOL, trim($this->_header)) as $line)
                $content .= $this->getComment($line);
            $content .=  $this->getComment(str_repeat('-', 75));
        }
        return $content;
    }

}
