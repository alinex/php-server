<?php
/**
 * @file
 * Test entity for or-mapper.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Entity;

/**
 * Test entity for or-mapper.
 *
 * @Entity
 *
 * @Table(name="test")
 */
class Test
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;
    
    /** @Column(type="string") **/
    protected $name;

}