<?php

namespace Alinex\Dictionary;

/**
 * ErrorHandler test case
 */
class RegistryTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = Registry::getInstance();
        $this->object->clear();
        $this->object->validatorClear();
    }

    function testGetInstance()
    {
        $registry = Registry::getInstance();
        $this->assertInstanceOf('\Alinex\Dictionary\Registry', $registry);
    }

    function testInitial()
    {
        $this->assertFalse($this->object->has('not_existing'));
        $this->assertNull($this->object->get('not_existing'));
    }

    /**
     * @depends testInitial
     */
    function testSetGet()
    {
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertEquals(123, $this->object->get('normalValue'));
        $this->assertTrue($this->object->has('normalValue'));
        $this->assertTrue($this->object->remove('normalValue'));
        $this->assertFalse($this->object->has('normalValue'));
        $this->assertFalse($this->object->remove('normalValue'));
        $this->assertNull($this->object->get('normalValue'));
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertTrue($this->object->has('normalValue'));
        $this->assertNull($this->object->set('normalValue'));
        $this->assertFalse($this->object->has('normalValue'));
    }

    /**
     * @depends testSetGet
     */
    function testArrayAccess()
    {
        $this->assertEquals(123, $this->object['normalValue'] = 123);
        $this->assertEquals(123, $this->object['normalValue']);
        $this->assertTrue(isset($this->object['normalValue']));
        unset($this->object['normalValue']);
        $this->assertFalse(isset($this->object['normalValue']));
        $this->assertNull($this->object['normalValue']);
    }

    /**
     * @depends testSetGet
     */
    function testGroupSetGet()
    {
        $this->assertTrue($this->object->groupSet('group_', array('v1' => 123, 'v2' => 234, 'v3' => 345)));
        $this->assertTrue($this->object->has('group_v1'));
        $this->assertTrue($this->object->has('group_v2'));
        $this->assertTrue($this->object->has('group_v3'));
        $this->assertEquals(3, count($this->object->groupGet('group_')));
        $this->assertTrue($this->object->groupClear('group_'));
        $this->assertCount(0, $this->object->keys());
        $this->assertFalse($this->object->groupClear('group_'));
        $this->assertEquals(0, count($this->object->groupGet('group_')));
    }

    function testKeysClear()
    {
        $this->object->set('firstValue', 1);
        $this->object->set('secondValue', 2);
        $this->assertEquals(2, $this->object->count());
        $this->assertCount(2, $this->object->keys());
        $this->assertTrue($this->object->clear());
        $this->assertCount(0, $this->object->keys());
        $this->assertFalse($this->object->clear());
    }

    /**
     * @depends testInitial
     */
    function testValidatorSetGet()
    {
        $validator = 'Alinex\Validator\Type::integer';
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorGet('normalValue'));
        $this->assertTrue($this->object->validatorHas('normalValue'));
        $this->assertTrue($this->object->validatorRemove('normalValue'));
        $this->assertFalse($this->object->validatorHas('normalValue'));
        $this->assertFalse($this->object->validatorRemove('normalValue'));
        $this->assertNull($this->object->validatorGet('normalValue'));
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertTrue($this->object->validatorHas('normalValue'));
        $this->assertNull($this->object->validatorSet('normalValue'));
        $this->assertFalse($this->object->validatorHas('normalValue'));
    }

    /**
     * @depends testValidatorSetGet
     */
    function testValidator()
    {
        $validator = 'Alinex\Validator\Type::integer';
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertTrue($this->object->validatorHas('normalValue'));
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorGet('normalValue'));
        $this->assertTrue(is_string($this->object->validatorDescription('normalValue')));
        $this->object->set('normalValue', '5');
        $this->assertEquals(5, $this->object->get('normalValue'));
        $this->setExpectedException('Alinex\Validator\Exception');
        $this->object->set('normalValue', '5.7');
    }

    function testValidatorClear()
    {
        $validator = 'Alinex\Validator\Type::integer';
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertTrue($this->object->validatorHas('normalValue'));
        $this->assertTrue($this->object->validatorClear());
        $this->assertFalse($this->object->validatorHas('normalValue'));
    }

    function testValidatorKeysClear()
    {
        $validator = 'Alinex\Validator\Type::integer';
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertCount(1, $this->object->validatorKeys());
    }

    function testValidatorGroup()
    {
        $validator = 'Alinex\Validator\Type::integer';
        // all
        $this->assertEquals(array(array('Alinex\Validator\Type', 'integer'), null), $this->object->validatorSet('normalValue', $validator));
        $this->assertTrue($this->object->validatorHas('normalValue'));
        $group = $this->object->validatorGroupGet('');
        $this->assertTrue(is_array($group));
        $this->assertTrue($this->object->validatorRemove('normalValue'));
        $this->assertFalse($this->object->validatorHas('normalValue'));
        $this->object->validatorGroupSet('', $group);
        $this->assertTrue($this->object->validatorHas('normalValue'));
        // subgroup
        $group = $this->object->validatorGroupGet('normal');
        $this->assertTrue(is_array($group));
        $this->assertTrue($this->object->validatorRemove('normalValue'));
        $this->assertFalse($this->object->validatorHas('normalValue'));
        $this->object->validatorGroupSet('normal', $group);
        $this->assertTrue($this->object->validatorHas('normalValue'));
    }

    function testRegistryWithoutValidators()
    {
        $registry = new Registry(Engine\ArrayList::getInstance());
        $this->setExpectedException('UnderflowException');
        $registry->validatorGet('xxx');
    }

    function testExportSections()
    {
        // fill up registry
        $this->object->validatorSet('float', 'Type::float', array(
            'description' => 'Eine kleine Zahl.',
            'minRange' => 1,
            'maxRange' => 10,
            'round' => 2
        ));
        $this->object->set('boolean', true);
        $this->object->set('integer', 5);
        $this->object->set('float', 5.3);
        $this->object->set('text.simple', "short text");
        $this->object->set('text.multiline', "line1\nline2\nline3");
        $this->object->set('text.empty', "");
        $this->object->set('array.simple', array(1,2,3));
        $this->object->set('array.hash', array('eins' => 1, 'zwei' => 2, 'drei' => 3));
        $this->object->set('array.array', array(array(1,2,3),array(4,5,6)));
        // export
        $file = __DIR__.'/../../data/registry-data.ini';
        $exporter = new ImportExport\IniFile();
        $exporter->setFile($file);
        $exporter->addHeader("Test of storage export/import");
        $exporter->setSections();
        $this->object->export($exporter);
        // export validator
        $file = __DIR__.'/../../data/registry-validator.ini';
        $exporter = new ImportExport\IniFile();
        $exporter->setFile($file);
        $exporter->addHeader("Test of storage export/import");
        $exporter->setSections();
        $this->object->validatorExport($exporter);
        // check file
        $file = __DIR__.'/not/existing/not-existing-registry.ini';
        $exporter->setFile($file);
        $this->setExpectedException('Alinex\Dictionary\ImportExport\Exception');
        $this->object->export($exporter);
    }

    /**
     * @ depends testExportSections
     */
    function testImportSections()
    {
        // import validators
        $file = __DIR__.'/../../data/registry-validator.ini';
        $importer = new ImportExport\IniFile();
        $importer->setFile($file);
        $importer->setSections();
        $this->object->validatorImport($importer);
        // import data
        $file = __DIR__.'/../../data/registry-data.ini';
        $importer->setFile($file);
        $this->object->import($importer);
        // check registry
        $this->assertEquals(true, $this->object->get('boolean')); // wrong datatype because of PHP lack
        $this->assertTrue($this->object->get('integer') === 5);
        $this->assertTrue($this->object->get('float') === 5.3);
        $this->assertTrue($this->object->get('text.simple') === "short text");
        $this->assertTrue($this->object->get('text.empty') === "");
        $this->assertEquals("line1\nline2\nline3", $this->object->get('text.multiline'));
        $this->assertEquals(array(1,2,3), $this->object->get('array.simple'));
        $this->assertEquals(array(array(1,2,3),array(4,5,6)), $this->object->get('array.array'));
        $this->assertEquals(array('eins' => 1, 'zwei' => 2, 'drei' => 3), $this->object->get('array.hash'));
        $this->assertEquals("Eine kleine Zahl.\nThe value has to be a floating point number. The value will be rounded to 2 digits after decimal point. The value has to be between 1 and 10.", $this->object->validatorDescription('float'));
    }

}
