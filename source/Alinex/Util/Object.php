<?php
/**
 * @file
 * Helpers working with objects.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Helpers working with objects.
 */
class Object
{
    /**
     * Get global object ID
     * From: http://stackoverflow.com/questions/2872366/get-instance-id-of-an-object-in-php
     * @param object $obj to analyze
     * @return int current object id in php memory hash
     */
    public function getId(&$obj) {
        assert(gettype($obj) == 'object');
        
        ob_start();
        var_dump($obj);// object(foo)#INSTANCE_ID (0) { }
        preg_match('~^.+?#(\d+)~s', ob_get_clean(), $oid);
        return $oid[1]; 
    }
}
