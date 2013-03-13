<?php
/**
 * @file
 * Autodetect import/export using URI.
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
 * Autodetect import/export using URI.
 *
 * This class will automatically import/export without specifiing the format
 * directly.
 *
 * Possible URI formats are:
 * - file:///path/to/file.type
 */
class Autodetect
{
    /**
     * Import registry entries from storage
     *
     * @param string $uri location of the import resource
     * @param Dictionary\Engine $engine Dictionary class to import to
     * @return bool TRUE on success
     */
    static function import($uri, Dictionary\Engine $engine)
    {
        return self::findInstance($uri, $engine)->import();
    }

    /**
     * Export registry entries to storage
     *
     * @param string $uri location of the export resource
     * @param Dictionary\Engine $engine Dictionary class to export from
     * @return bool TRUE on success
     */
    static function export($uri, Dictionary\Engine $engine)
    {
        return self::findInstance($uri, $engine)->export();
    }

    /**
     * Create an ImportExport instance for the given URI.
     *
     * @param string $uri location of the external resource
     * @param Dictionary\Engine $engine Dictionary class to export or import
     * @return Dictionary\ImportExport preset instance to work with
     * @throws Exception if no instance for this uri is found
     */
    public static function findInstance($uri, Dictionary\Engine $engine = null)
    {
        $data = array();
        if (!preg_match(
            '#^(?P<protocol>[a-z]{3,})://'
            .'(?P<path>.*?\.'
            .   '(?P<extension>[^.]+)'
            .')$#',
            $uri, $data
        )) throw new Exception(
            tr(
                __NAMESPACE__,
                'Could not parse import/export URI {uri}',
                array('uri' => $uri)
            )
        );
        // search for importer
        if ($data['protocol'] == 'file') {
            // search for file importer
            switch (strtolower($data['extension'])) {
                case 'ini':
                    return new IniFile($engine, $data['path']);
                case 'json':
                    return new JsonFile($engine, $data['path']);
                case 'php':
                case 'inc':
                    return new PhpFile($engine, $data['path']);
                case 'yaml':
                    return new YamlFile($engine, $data['path']);
                default:
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'Fileextension of {uri} is not recognized',
                            array('uri' => $uri)
                        )
                    );
            }
        }
        throw new Exception(
            tr(
                __NAMESPACE__,
                'Could not find ImportExport instance for protocol {protocol} in {uri}',
                array('protocol' => $data['protocol'], 'uri' => $uri)
            )
        );
    }
}
