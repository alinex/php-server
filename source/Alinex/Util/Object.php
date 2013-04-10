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
     *
     * This will get an global unique number for objects of this type.
     *
     * @attention Objects of other classes may have the same id.
     *
     * @param object $obj to analyze
     * @return int current object id in php memory hash
     * @see spl_object_hash($obj) for an completely unique hash id
     */
    public static function getId(&$obj)
    {
        assert(gettype($obj) == 'object');

        ob_start();
        var_dump($obj);// object(foo)#INSTANCE_ID (0) { }
        $oid = array();
        preg_match('~^.+?#(\d+)~s', ob_get_clean(), $oid);
        return $oid[1];
    }
}
