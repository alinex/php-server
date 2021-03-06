<?php
/**
 * @file
 * Import and export registry values using php-style file.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\ImportExport;

/**
 * Import and export hashtable values using php-style file.
 *
 * This can be reincluded in PHP using include().
 *
 * @verbinclude Alinex/Dictionary/ImportExport/storage.php
 */
class PhpFile extends File
{
    /**
     * Prefix to be used in comment lines before the text.
     */
    const COMMENT_PREFIX = '// ';

    /**
     * Import hashtable entries from ini file
     *
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function import()
    {
        assert($this->check());

        $this->checkReadable();
        include($this->_file);
        // set values
        if (isset($values))
            $this->setValues($values);
    }

    /**
     * Export hashtable entries to ini file
     *
     * @param array $commentkeys list of keys
     * @return bool TRUE on success
     * @throws Exception if storage can't be used
     */
    function export(array $commentkeys = null)
    {
        assert($this->check());

        // create export string
        $content =  '<?php '.PHP_EOL;
        // create header
        $content .= $this->getCommentHeader();
        // add entries
        $list = $this->getValues();
        if (isset($list)) {
            if (isset($commentkeys))
                $keys = array_merge($commentkeys, array_keys($list));
            else
                $keys = array_keys($list);
            sort($keys);
            foreach ($keys as $key) {
                $content .= PHP_EOL; // empty lines between entries
                if (isset($this->_commentCallback)) {
                    $content .= $this->getCommentLines($key);
                    if (!isset($list[$key]))
                        $content .= $this->getComment($key.' = ');
                }
                if (isset($list[$key])) {
                    if (is_int($key))
                        $content .= '$values['.$key.'] = '
                            .var_export($list[$key], 1).';'.PHP_EOL;
                    else
                        $content .= '$values[\''.$key.'\'] = '
                            .var_export($list[$key], 1).';'.PHP_EOL;
                }
            }
            $content .= PHP_EOL;
        }
        // write to file
        $this->checkWritable();
        file_put_contents($this->_file, $content);
    }

}
