<?php
/**
 * @file
 * Import and export hashtable values using ini-style file.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\ImportExport;

use Alinex\Util\String;
use Alinex\Util\ArrayStructure;

/**
 * Import and export hashtable values using ini-style file.
 *
 * @note
 * This export/import is limited to simple, scalar and array values. Complex
 * values will generate errors.
 *
 * The ini file may contain two sections:
 * - values - for the registry entries
 * - validators - to also store the validation rules
 *
 * Each entry may also contain '-' characters in it's key name to specify the
 * array element (also multidimensional) where it belongs to.
 *
 * In the export comments in form of an file header, section headers and field
 * descriptions out of the validators will be added.
 *
 * @verbinclude Alinex/Dictionary/ImportExport/registry-data.ini
 *
 * @attention Booleans won't be red with correct datatype in PHP.
 */
class IniFile extends File
{
    /**
     * Prefix to be used in comment lines before the text.
     */
    const COMMENT_PREFIX = '; ';

    /**
     * Separator to use for top section separator
     * @var string
     */
    protected $_sections = false;

    /**
     * Enable use of sections using defined separator.
     *
     * If the Ini-file should use sections for the top groups a separator has to
     * be given.
     * @param string $separator character to separate top section from key
     */
    public function setSections($separator = '.')
    {
        $this->_sections = $separator;
    }

    /**
     * Import registry entries from ini file
     *
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function import()
    {
        assert($this->check());

        $this->checkReadable();
        $content = parse_ini_file(
            $this->_file, $this->_sections ? true : false
        );
        if ($content === false)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The ini-values from {file} could not be read',
                    array('file' => String::dump($this->_file))
                )
            );
        // create arrays from content
        $result = array();
        if (!$this->_sections) {
            $result = array();
            foreach ($content as $path => $value) {
                if (is_string($value))
                    $value = str_replace('\\n', PHP_EOL, $value);
                ArrayStructure::set(
                    String::convertType($value),
                    $result,
                    $path,
                    '-'
                );
            }
        } else {
            $result = array();
            foreach ($content as $section => $values) {
                if (is_array($values)) {
                    foreach ($values as $path => $value) {
                        if (is_string($value))
                            $value = str_replace('\\n', PHP_EOL, $value);
                        ArrayStructure::set(
                            String::convertType($value),
                            $result,
                            $section.$this->_sections.$path,
                            '-'
                        );
                    }
                } else {
                    if (is_string($values))
                        $value = str_replace('\\n', PHP_EOL, $values);
                    ArrayStructure::set(
                        String::convertType($value),
                        $result,
                        $section,
                        '-'
                    );
                }
            }
        }
        // set values
        if (isset($result))
            $this->setValues($result);
    }

    /**
     * Export registry entries to ini file
     *
     * @param array $commentkeys list of keys
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function export(array $commentkeys = null)
    {
        assert($this->check());

        $content = '';
        // create header
        $content .= $this->getCommentHeader();
        $list = $this->getValues();
        if (isset($list)) {
            if (isset($commentkeys))
                $keys = array_merge($commentkeys, array_keys($list));
            else
                $keys = array_keys($list);
            sort($keys);
            if ($this->_sections) {
                $currentSection = null;
                // write simple values without section first
                foreach ($keys as $key) {
                    if (strpos($key, $this->_sections) !== false)
                        continue;
                    $content .= PHP_EOL; // empty lines between entries
                    if (isset($this->_commentCallback)) {
                        $content .= $this->getCommentLines($key);
                        if (!isset($list[$key]))
                            $content .= $this->getComment($key.' = ');
                    }
                    if (isset($list[$key]))
                        self::_writeIniLine($content, $key, $list[$key]);
                }
                // write sections
                foreach ($keys as $key) {
                    if (strpos($key, $this->_sections) === false)
                        continue;
                    $content .= PHP_EOL; // empty lines between entries
                    list($section, $subkey) = explode($this->_sections, $key, 2);
                    if ($section != $currentSection) {
                        // add section header
                        $content .= PHP_EOL.'['.$section.']'.PHP_EOL.PHP_EOL;
                        $currentSection = $section;
                    }
                    if (isset($this->_commentCallback)) {
                        $content .= $this->getCommentLines($key);
                        if (!isset($list[$key]))
                            $content .= $this->getComment($key.' = ');
                    }
                    if (isset($list[$key]))
                        self::_writeIniLine($content, $subkey, $list[$key]);
                }
                $content .= PHP_EOL;
            } else {
                foreach ($keys as $key) {
                    $content .= PHP_EOL; // empty lines between entries
                    if (isset($this->_commentCallback)) {
                        $content .= $this->getCommentLines($key);
                        if (!isset($list[$key]))
                            $content .= $this->getComment($key.' = ');
                    }
                    if (isset($list[$key]))
                        self::_writeIniLine($content, $key, $list[$key]);
                }
                $content .= PHP_EOL;
            }
        }
        // write file
        $this->checkWritable();
        file_put_contents($this->_file, $content);
    }

    /**
     * Get the comment lines.
     * @param string $key
     * @return string
     */
    private function getCommentLines($key)
    {
        $content = '';
        // add group name if removed
        if (isset($this->_group))
            $key .= $this->_group;
        $comment = call_user_func($this->_commentCallback, $key);
        if ($comment)
            $content .= $this->getComment($comment);
        return $content;
    }

    /**
     * Write ini line (block) for one value.
     *
     * This may write a block of lines if the value is an array. Therefore
     * this method is called recursively.
     *
     * @param string $content reference to which the lines will be added
     * @param string $key name of the entry
     * @param mixed $value value of the entry
     */
    private static function _writeIniLine(&$content, $key, $value)
    {
        assert(is_string($content));

        if (is_array($value))
            foreach ($value as $ikey => $ivalue)
                self::_writeIniLine($content, $key.'-'.$ikey, $ivalue);
        else
            $content .= $key.' = '.self::_formatValue($value).PHP_EOL;
    }

    /**
     * Replacement in values for export.
     * @var array
     */
    private static $_exportReplace = array(
        "\\" => '\\\\', # \ (a single backslash, escaping the escape character)
        "\0" => '\\0',  # Null character
        "\a" => '\\a',  # Bell/Alert/Audible
        "\b" => '\\b',  # Backspace, Bell character for some applications
        "\t" => '\\t',  # Tab character
        "\r" => '\\r',  # Carriage return
        "\n" => '\\n',  # Newline
        '; ' => '\\;',  # Semicolon
        '#'  => '\\#',  # Number sign
        '='  => '\\=',  # Equals sign
        '"'  => '\\"',  # Equals sign
        ':'  => '\\:'   # Colon
    );

    /**
     * Format value for ini export.
     *
     * @param scalar $value value to format
     * @return mixed value which can be added as string
     */
    private static function _formatValue($value)
    {
        assert(is_scalar($value));

        if (is_bool($value))
            return $value ? 'true' : 'false';
        if (is_numeric($value))
            return $value;
        // as string
        return '"'.str_replace(
            array_keys(self::$_exportReplace),
            array_values(self::$_exportReplace),
            $value
        ).'"';
    }
}
