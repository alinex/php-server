<?php
/**
 * @file
 * Import and export registry values using YAML file.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\ImportExport;

use Alinex\Dictionary;

/**
 * Import and export hashtable values using YAML file.
 *
 * @note
 * This will use the PECL extension under http://pecl.php.net/package/yaml if
 * installed. If not it will use a pure php implementation to work.
 *
 * @verbinclude Alinex/Dictionary/ImportExport/storage.yaml
 */
class YamlFile extends File
{
    /**
     * Prefix to be used in comment lines before the text.
     */
    const COMMENT_PREFIX = '#';

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
        // load the php spyc implementation if no native yaml supported
        if (!extension_loaded('yaml'))
          require_once __DIR__.'/../../../vnd/spyc/Spyc.php';
        
        parent::__construct($data, $file);
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
        // set values
        if (extension_loaded('yaml'))
            $import = yaml_parse($content);
        else
            $import = \Spyc::YAMLLoadString($yaml);
        if ($import === false)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Could not parse the YAML in {file}',
                    array('file' => $this->_file)
                )
            );
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
        $content .= $this->getCommentHeader();
        // create export string
        if (extension_loaded('yaml'))
            $content .= yaml_emit($this->getValues());
        else
            $content .= \Spyc::YAMLDump($this->getValues());
        // add comments
        if (isset($this->_commentCallback)) {
            foreach (array_keys($this->getValues()) as $key) {
                $comment = $this->getCommentLines($key);
                // add on specific position
                $content = preg_replace(
                    '/^\w+'.$key.':/m', $comment.'$1', $content
                );
            }
        }
        $this->checkWritable();
        file_put_contents($this->_file, $content);
    }

}
