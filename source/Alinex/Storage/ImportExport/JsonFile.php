<?php
/**
 * @file
 * Import and export registry values using json file.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage\ImportExport;

/**
 * Import and export hashtable values using json file.
 *
 * This class may add comments in c-style syntax which are not standard
 * conform to the JSON definition but will be removed on import. To use standard
 * conform JSON you may switch comments off.
 *
 * @verbinclude Alinex/Storage/ImportExport/storage.json
 */
class JsonFile extends File
{
    /**
     * Prefix to be used in comment lines before the text.
     */
    const COMMENT_PREFIX = '/* ';

    /**
     * Postfix used in comment lines behind the text.
     */
    const COMMENT_POSTFIX = ' */';

    /**
     * Should comments be added (not valid json)
     * @var bool
     */
    protected $_comments = true;

    /**
     * Should comments in be added (not standard conform)
     *
     * If set additional comments will be added in c-style form. This breaks
     * the json standard but is often done so.
     * @param bool $comments true if comments should be used
     */
    public function useComments($comments = true)
    {
        $this->_comments = $comments;
    }

    /**
     * Import hashtable entries from json file
     *
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function import()
    {
        assert($this->check());

        $this->checkReadable();
        $content = file_get_contents($this->_file);
        // remove comment lines with only comment
        if ($this->_comments)
            $content = preg_replace('/^\w*\/\*.*?\*\/\w*$/m', '', $content);
        // set values
        $import = json_decode($content, true);
        if (json_last_error())
            throw new Exception(json_last_error());
        if (isset($import))
            $this->setValues($import);
    }

    /**
     * Export hashtable entries to json file
     *
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function export()
    {
        assert($this->check());

        // create export string
        $content =  '';
        // create header
        if ($this->_comments)
            $content .= $this->getCommentHeader();
        // create export string
        if (phpversion() >= 5.4)
            $content .= json_encode(
                $this->getValues(),
                JSON_PRETTY_PRINT && JSON_UNESCAPED_UNICODE
            );
        else
            $content .= $this->json_readable_encode(
                $this->getValues()
            );
        // add comments
        if ($this->_comments && isset($this->_commentCallback)) {
            foreach ($this->getValues() as $key => $value) {
                $comment = $this->getCommentLines($key);
                // add on specific position
                $content = preg_replace('/^\w+"'.$key.'"/m', $comment.'$1', $content);
            }
        }
        $this->checkWritable();
        file_put_contents($this->_file, $content);
    }

    /**
     * Create readable version of json data.
     *
     * @param string $in json string to pretty print
     * @param int $indent current indention level
     * @param \Closure $_escape escape function for string values
     * @return string in pretty indented format
     *
     * @todo remove $_escape closure with String:: call
     */
    function json_readable_encode($in, $indent = 0, \Closure $_escape = null)
    {
        $_myself = array($this, __FUNCTION__);
        if (is_null($_escape)) {
            $_escape = function ($str) {
                return str_replace(
                    array(
'\\', '"', "\n", "\r", "\b", "\f", "\t", '/', '\\\\u'
                    ),
                    array(
'\\\\', '\\"', "\\n", "\\r", "\\b", "\\f", "\\t", '\\/', '\\u'
                    ),
                    $str
                );
            };
        }
        $out = '';
        foreach ($in as $key=>$value) {
            $out .= str_repeat("\t", $indent + 1);
            $out .= "\"".$_escape((string)$key)."\": ";

            if (is_object($value) || is_array($value)) {
                $out .= "\n";
                $out .= call_user_func($_myself, $value, $indent + 1, $_escape);
            } else if (is_bool($value)) {
                $out .= $value ? 'true' : 'false';
            } else if (is_null($value)) {
                $out .= 'null';
            } else if (is_string($value)) {
                $out .= "\"" . $_escape($value) ."\"";
            } else {
                $out .= $value;
            }
            $out .= ",\n";
        }

        if (!empty($out))
            $out = substr($out, 0, -2);
        $out = str_repeat("\t", $indent) . "{\n" . $out;
        $out .= "\n" . str_repeat("\t", $indent) . "}";

        return $out;
    }
}
