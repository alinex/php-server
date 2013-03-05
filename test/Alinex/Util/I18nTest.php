<?php
/**
 * @file
 * Unit tests for ArrayStructure.
 *
 * @author Alexander Schilling <info@alinex.de>
 * @copyright \ref Copyright (c) 2009 - 2013, Alexander Schilling
 * @license All Alinex code is released under the GNU General Public \ref License.
 * @see       @link http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Unit tests for ArrayUtils
 */
class I18nTest extends \PHPUnit_Framework_TestCase
{
    function testSimple()
    {
        $this->assertEquals('Test entry', I18n::test());
    }
}
