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
class ArrayStructureTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function testSet()
    {
        $data = array();
        $arrValue = array(1, 2, 3, 4);

        ArrayStructure::set(1, $data, 'erster');
        $this->assertArrayHasKey('erster', $data, "simple key is not set");
        $this->assertEquals(1, $data['erster'], "simple value is not stored");

        ArrayStructure::set(2, $data, array('zweiter','wert'));
        $this->assertArrayHasKey('zweiter', $data, "path array is not set");
        $this->assertArrayHasKey('wert', $data['zweiter'], "path array is not set");
        $this->assertEquals(2, $data['zweiter']['wert'], "value is not stored in path");

        ArrayStructure::set(3, $data, 'dritter.wert', '.');
        $this->assertArrayHasKey('dritter', $data, "path is not set");
        $this->assertArrayHasKey('wert', $data['dritter'], "path is not set");
        $this->assertEquals(3, $data['dritter']['wert'], "value is not stored in path");

        ArrayStructure::set($arrValue, $data, 'list');
        $this->assertArrayHasKey('list', $data, "array value is not set");
        $this->assertEquals($arrValue, $data['list'], "stored array value is not correct");

        return $data;
    }

    /**
     * @depends testSet
     */
    public function testGet(array $data)
    {
        $arrValue = array(1, 2, 3, 4);

        $value = ArrayStructure::get($data, 'erster');
        $this->assertEquals(1, $value, "get simple key");

        $value = ArrayStructure::get($data, array('zweiter','wert'));
        $this->assertEquals(2, $value, "get path array value");

        $value = ArrayStructure::get($data, 'dritter.wert', '.');
        $this->assertEquals(3, $value, "get path value");

        $value = ArrayStructure::get($data, 'list');
        $this->assertEquals($arrValue, $value, "get array value");
    }

    /**
     * @depends testSet
     */
    public function testRemove(array $data)
    {
        ArrayStructure::remove($data, 'erster');
        $this->assertArrayNotHasKey('erster', $data, "unset simple key");

        ArrayStructure::remove($data, array('zweiter','wert'));
        $this->assertArrayNotHasKey('wert', $data['zweiter'], "unset path array value");

        ArrayStructure::remove($data, 'dritter.wert', '.');
        $this->assertArrayNotHasKey('wert', $data['dritter'], "unset path value");

        ArrayStructure::remove($data, 'list');
        $this->assertArrayNotHasKey('list', $data, "unset aarray value");
    }

    /**
     * @depends testSet
     */
    public function testHas(array $data)
    {
        $value = ArrayStructure::has($data, 'erster');
        $this->assertTrue($value, "check simple key");

        $value = ArrayStructure::has($data, array('zweiter','wert'));
        $this->assertTrue($value, "check path array value");

        $value = ArrayStructure::has($data, 'dritter.wert', '.');
        $this->assertTrue($value, "check path value");

        $value = ArrayStructure::has($data, 'list');
        $this->assertTrue($value, "check path is set");
    }

    /**
     * @expectedException Exception
     */
    public function testSetException()
    {
        $testarray = array();
        ArrayStructure::set(18, $testarray, NULL);
    }

    /**
     * @expectedException Exception
     */
    public function testHasException()
    {
        ArrayStructure::has(array(), NULL);
    }

    public function testGetNotExisting()
    {
        $data = array();
        $this->assertNull(ArrayStructure::get($data, 'not.existing', '.'));
    }

}
