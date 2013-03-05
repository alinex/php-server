<?php

namespace Alinex\Validator;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    function testBoolean()
    {
        $this->assertTrue(is_string(Type::booleanDescription()));
        $this->assertTrue(Type::boolean(true, 'true is true'));
        $this->assertTrue(Type::boolean(1, '1 is true'));
        $this->assertTrue(Type::boolean('true', 'true is true'));
        $this->assertTrue(Type::boolean('TRUE', 'TRUE is true'));
        $this->assertTrue(Type::boolean('True', 'True is true'));
        $this->assertTrue(Type::boolean('1', '1 is true'));
        $this->assertTrue(Type::boolean('on', 'on is true'));
        $this->assertTrue(Type::boolean('On', 'On is true'));
        $this->assertTrue(Type::boolean('yes', 'yes is true'));
        $this->assertTrue(Type::boolean('Yes', 'Yes is true'));
        $this->assertFalse(Type::boolean(false, 'false is false'));
        $this->assertFalse(Type::boolean(0, '0 is false'));
        $this->assertFalse(Type::boolean('false', 'false is false'));
        $this->assertFalse(Type::boolean('False', 'False is false'));
        $this->assertFalse(Type::boolean('FALSE', 'FALSE is false'));
        $this->assertFalse(Type::boolean('0', '0 is false'));
        $this->assertFalse(Type::boolean('off', 'off is false'));
        $this->assertFalse(Type::boolean('Off', 'Off is false'));
        $this->assertFalse(Type::boolean('no', 'no is false'));
        $this->assertFalse(Type::boolean('No', 'No is false'));
        $this->assertFalse(Type::boolean('', 'empty string is false'));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::boolean('maybe', 'maybe should fail');
    }

    function testIntegerNormal()
    {
        $this->assertTrue(is_string(Type::integerDescription()));
        $this->assertTrue(is_string(Type::integerDescription(
            array('sanitize' => true, 'minRange' => 5)
        )));
        $this->assertTrue(is_string(Type::integerDescription(
            array('allowOctal' => true, 'minRange' => 5, 'maxRange' => 7)
        )));
        $this->assertTrue(is_string(Type::integerDescription(
            array('allowHex' => true, 'maxRange' => 7)
        )));
        $this->assertTrue(is_string(Type::integerDescription(
            array('allowFloat' => true, 'round' => true)
        )));
        $this->assertEquals(5, Type::integer(5, 'test'), 'integer used');
        $this->assertEquals(-5, Type::integer(-5, 'test'), 'negative integer');
        $this->assertEquals(5, Type::integer(5.0, 'test'), 'float used');
        $this->assertEquals(5, Type::integer("5", 'test'), 'integer as string used');
        $this->assertEquals(5, Type::integer("+5", 'test'), 'positive integer as string used');
        $this->assertEquals(-5, Type::integer("-5", 'test'), 'negative integer as string used');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(5.4, 'test');
    }

    function testIntegerMinRange()
    {
        $options = array('minRange' => 5);
        $this->assertEquals(500, Type::integer(500, 'test', $options), 'big value');
        $this->assertEquals(5, Type::integer(5, 'test', $options), 'minRange value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(4, 'test', $options);
    }

    function testIntegerMaxRange()
    {
        $options = array('maxRange' => 50);
        $this->assertEquals(5, Type::integer(5, 'test', $options), 'small value');
        $this->assertEquals(50, Type::integer(50, 'test', $options), 'maxRange value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(500, 'test', $options);
    }

    function testIntegerTypeMin()
    {
        $options = array('type' => 'byte');
        $this->assertEquals(-128, Type::integer(-128, 'test', $options), 'min value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(-129, 'test', $options);
    }

    function testIntegerTypeMax()
    {
        $options = array('type' => 'byte');
        $this->assertEquals(127, Type::integer(127, 'test', $options), 'max value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(128, 'test', $options);
    }

    function testIntegerUnsigned()
    {
        $options = array('unsigned' => true);
        $this->assertEquals(0, Type::integer(0, 'test', $options), 'min value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(-1, 'test', $options);
    }

    function testIntegerTypeUnsigned()
    {
        $options = array('type' => 'byte', 'unsigned' => true);
        $this->assertEquals(255, Type::integer(255, 'test', $options), 'max value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(256, 'test', $options);
    }

    function testIntegerSanitize()
    {
        $options = array('sanitize' => true);
        $this->assertEquals(5, Type::integer('+5', 'test', $options), 'positive value');
        $this->assertEquals(-5, Type::integer('-5', 'test', $options), 'negative value');
        $this->assertEquals(50, Type::integer('5.0', 'test', $options), 'float value');
        $this->assertEquals(50, Type::integer('50%', 'test', $options), 'percent value');
        $this->assertEquals(5, Type::integer('over 5 seconds', 'test', $options), 'value in text');
    }

    function testIntegerOctal()
    {
        $options = array('allowOctal' => true);
        $this->assertEquals(3, Type::integer('3', 'test', $options), 'normal number');
        $this->assertEquals(9, Type::integer('9', 'test', $options), 'normal number');
        $this->assertEquals(99, Type::integer('99', 'test', $options), 'higher nujmber');
        $this->assertEquals(9, Type::integer('011', 'test', $options), 'octal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer('09', 'test', $options);
    }

    function testIntegerFloat()
    {
        $options = array('allowFloat' => true);
        $this->assertEquals(5, Type::integer(5.0, 'test', $options), 'float used');
        $this->assertEquals(5, Type::integer('5.0', 'test', $options), 'float as string');
        $options['round'] = true;
        $this->assertEquals(5, Type::integer(4.5, 'test', $options), 'rounding up');
        $this->assertEquals(5, Type::integer(5.49, 'test', $options), 'rounding down');
        unset($options['round']);
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::integer(4.1, 'test', $options);
    }

    function testFloatNormal()
    {
        $this->assertTrue(is_string(Type::floatDescription()));
        $this->assertTrue(is_string(Type::floatDescription(
            array('decimal' => ',', 'minRange' => 10)
        )));
        $this->assertTrue(is_string(Type::floatDescription(
            array('sanitize' => true, 'maxRange' => 50)
        )));
        $this->assertTrue(is_string(Type::floatDescription(
            array('round' => 2, 'minRange' => 10, 'maxRange' => 50)
        )));
        $this->assertEquals(5, Type::float(5, 'test'), 'integer used');
        $this->assertEquals(-5, Type::float(-5, 'test'), 'negative integer');
        $this->assertEquals(5.0, Type::float(5.0, 'test'), 'float used');
        $this->assertEquals(5.199, Type::float(5.199, 'test'), 'float used');
        $this->assertEquals(5, Type::float("5", 'test'), 'integer as string used');
        $this->assertEquals(5, Type::float("+5", 'test'), 'positive integer as string used');
        $this->assertEquals(-5, Type::float("-5", 'test'), 'negative integer as string used');
        $this->assertEquals(5.0, Type::float("5.0", 'test'), 'float as string used');
        $this->assertEquals(5.199, Type::float("5.199", 'test'), 'float as string used');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::float('one point nine million', 'free text should not be possible');
    }

    function testFloatSanitize()
    {
        $options = array('sanitize' => true);
        $this->assertEquals(5, Type::float('+5', 'test', $options), 'positive value');
        $this->assertEquals(-5, Type::float('-5', 'test', $options), 'negative value');
        $this->assertEquals(5.0, Type::float('5.0', 'test', $options), 'float value');
        $this->assertEquals(50, Type::float('50%', 'test', $options), 'percent value');
        $this->assertEquals(5, Type::float('over 5 seconds', 'test', $options), 'value in text');
        $this->assertEquals(5.199, Type::float('5.199', 'test', $options), 'float value as string');
        $this->assertEquals(50.7, Type::float('50.7%', 'test', $options), 'floar percent value');
        $this->assertEquals(5.8, Type::float('over 5.8 meter', 'test', $options), 'float in text');
    }

    function testFloatMinRange()
    {
        $options = array('minRange' => 5);
        $this->assertEquals(500, Type::float(500, 'test', $options), 'big value');
        $this->assertEquals(5, Type::float(5.0, 'test', $options), 'minRange value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::float(4.9, 'test', $options);
    }

    function testFloatMaxRange()
    {
        $options = array('maxRange' => 50);
        $this->assertEquals(5, Type::float(5, 'test', $options), 'small value');
        $this->assertEquals(50, Type::float(50, 'test', $options), 'maxRange value');
        $options = array('minRange' => 10, 'maxRange' => 50);
        $this->assertEquals(50, Type::float(50, 'test', $options), 'minRange and maxRange');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::float(500, 'test', $options);
    }

    function testFloatUnsigned()
    {
        $options = array('unsigned' => true);
        $this->assertEquals(0, Type::float(0, 'test', $options), 'min value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::float(-0.3, 'test', $options);
    }

    function testFloatRound()
    {
        $this->assertEquals(3, Type::float(2.5, 'test', array('round' => 0)), 'round up to full number');
        $this->assertEquals(3, Type::float(3.49, 'test', array('round' => 0)), 'round down to full number');
        $this->assertEquals(3.14, Type::float(3.1415, 'test', array('round' => 2)), 'round to two digits');
    }

    function testStringNormal()
    {
        $this->assertEquals('test', Type::string('test', 'test'), 'simple string');
        $this->assertEquals('', Type::string('', 'test'), 'empty string');
        $this->assertEquals('testctrl', Type::string("test\x00ctrl", 'test'), 'control char');
        $this->assertEquals("test\x00ctrl", Type::string("test\x00ctrl", 'test',
            array('allowControls' => true)), 'allowed control char');
        $this->assertEquals("ernie und bert", Type::string('user1 und user2', 'test',
            array('replace' => array('user1' => 'ernie', 'user2' => 'bert'))), 'replace strings');
        $this->assertEquals("ernie oh ernie", Type::string('user1 oh user1', 'test',
            array('replace' => array('user1' => 'ernie', 'user2' => 'bert'))), 'replace strings multiple');
        $this->assertEquals("user 1 use computer 4", Type::string('u1 use c4', 'test',
            array('replace' => array('/u(\\d)/' => 'user $1', '/c(\\d)/' => 'computer $1'))), 'pregReplace strings');
        $this->assertEquals("user 1 loves user 4", Type::string('u1 loves u4', 'test',
            array('replace' => array('/u(\\d)/' => 'user $1', '/c(\\d)/' => 'computer $1'))), 'pregReplace strings multiple');
        $this->assertEquals("please <b>stop</b>", Type::string('please <b>stop</b>', 'test'), 'allow tags');
        $this->assertEquals("please stop", Type::string('please <b>stop</b>', 'test',
            array('stripTags' => true)), 'strip tags');
        $this->assertEquals("  trim  this  ", Type::string('  trim  this  ', 'test'), 'trim not');
        $this->assertEquals("trim  this", Type::string('  trim  this  ', 'test',
            array('trim' => true)), 'trim');
        $this->assertEquals("123456", Type::string('1234567890', 'test',
            array('crop' => 6)), 'crop');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string(array(), 'test');
    }

    function testStringMinLength()
    {
        $options = array('minLength' => 3);
        $this->assertEquals('test', Type::string('test', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('a', 'test', $options);
    }

    function testStringMaxLength()
    {
        $options = array('maxLength' => 8);
        $this->assertEquals('test', Type::string('test', 'test', $options), 'normal value');
        $this->assertEquals('', Type::string('', 'test', $options), 'empty string');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('this is too long', 'test', $options);
    }

    function testStringWhitelist()
    {
        $options = array('whitelist' => '0123456789absdef');
        $this->assertEquals('0d6e2f', Type::string('0d6e2f', 'test', $options), 'normal value');
        $this->assertEquals('', Type::string('', 'test', $options), 'empty string');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('darkred', 'test', $options);
    }

    function testStringBlacklist()
    {
        $options = array('blacklist' => '0123456789');
        $this->assertEquals('one two three', Type::string('one two three', 'test', $options), 'normal value');
        $this->assertEquals('', Type::string('', 'test', $options), 'empty string');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('one 2 three', 'test', $options);
    }

    function testStringMatchString()
    {
        $options = array('match' => 'done');
        $this->assertEquals('processing done', Type::string('processing done', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('processing failed', 'test', $options);
    }

    function testStringMatchRegexp()
    {
        $options = array('match' => '/(done|100%)/');
        $this->assertEquals('progress 100%...', Type::string('progress 100%...', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('progress 90%...', 'test', $options);
    }

    function testStringMatchNotString()
    {
        $options = array('matchNot' => 'failed');
        $this->assertEquals('processing done', Type::string('processing done', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('processing failed', 'test', $options);
    }

    function testStringMatchNotRegexp()
    {
        $options = array('matchNot' => '/(done|100%)/');
        $this->assertEquals('progress 90%...', Type::string('progress 90%...', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('progress 100%...', 'test', $options);
    }

    function testStringValues()
    {
        $options = array('values' => array('spring','summer','autumn','winter'));
        $this->assertEquals('summer', Type::string('summer', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('april', 'test', $options);
    }

    function testStringStartsWith()
    {
        $options = array('startsWith' => 'click');
        $this->assertEquals('click on button', Type::string('click on button', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('push button', 'test', $options);
    }

    function testStringEndsWith()
    {
        $options = array('endsWith' => 'button');
        $this->assertEquals('click on button', Type::string('click on button', 'test', $options), 'normal value');
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::string('click the mouse', 'test', $options);
    }

    function testStringDescription()
    {
        $this->assertTrue(is_string(Type::stringDescription()));
        $this->assertTrue(is_string(Type::stringDescription(
            array(
                'values' => array('one', 'two')
            )
        )));
        $this->assertTrue(is_string(Type::stringDescription(
            array(
                'allowControls' => true,
                'replace' => array('1' => 'one'),
                'pregReplace' => array('/1/' => ''),
                'stripTags' => true,
                'trim' => true,
                'crop' => 6,
                'minLength' => 1,
                'maxLength' => 8,
                'whitelist' => 'abcedfg',
                'blacklist' => '012345678',
                'match' => 'one',
                'matchNot' => '1',
                'startsWith' => 'o',
                'endsWith' => 'e'
            )
        )));
        $this->assertTrue(is_string(Type::stringDescription(
            array('minLength' => 1)
        )));
        $this->assertTrue(is_string(Type::stringDescription(
            array('maxLength' => 8)
        )));
        $this->assertTrue(is_string(Type::stringDescription(
            array('replace' => array('1' => 'one'))
        )));
        $this->assertTrue(is_string(Type::stringDescription(
            array('allowControls' => false)
        )));
    }

    function testArray()
    {
        $this->assertTrue(is_array(Type::arraylist(array(), 'test')));
        $this->assertTrue(is_array(Type::arraylist('a,b,c', 'test',
            array('delimiter' => ','))));
        $this->assertTrue(is_array(Type::arraylist('a, b, c', 'test',
            array('delimiter' => '/,\s*/'))));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist(array(), 'test', array('notEmpty' => true));
    }

    function testArrayMissingDelimiter()
    {
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist('a,b,c', 'test');
    }

    function testArrayMandatoryKeys()
    {
        $options = array('mandatoryKeys' => array('name'));
        $this->assertTrue(is_array(Type::arraylist(array('name' => 'alex', 'gender' => 'm'), 'test', $options)));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist(array('company' => 'ibm'), 'test', $options);
    }

    function testArrayAllowedKeys()
    {
        $options = array('allowedKeys' => array('name', 'gender'));
        $this->assertTrue(is_array(Type::arraylist(array('name' => 'alex', 'gender' => 'm'), 'test', $options)));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist(array('name' => 'alex', 'gender' => 'm', 'company' => 'ibm'), 'test', $options);
    }

    function testArrayMinLength()
    {
        $options = array('minLength' => 2);
        $this->assertTrue(is_array(Type::arraylist(array('name' => 'alex', 'gender' => 'm'), 'test', $options)));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist(array('company' => 'ibm'), 'test', $options);
    }

    function testArrayMaxLength()
    {
        $options = array('maxLength' => 2);
        $this->assertTrue(is_array(Type::arraylist(array('name' => 'alex', 'gender' => 'm'), 'test', $options)));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::arraylist(array('name' => 'alex', 'gender' => 'm', 'company' => 'ibm'), 'test', $options);
    }

    function testArraylistDescription()
    {
        $this->assertTrue(is_string(Type::arraylistDescription(
            array(
                'delimiter' => ',',
                'notEmpty' => true,
                'mandatoryKeys' => array('name', 'gender'),
                'allowedKeys' => array('company'),
                'minLength' => 2,
                'maxLength' => 3
            )
        )));
        $this->assertTrue(is_string(Type::arraylistDescription(
            array('minLength' => 1)
        )));
        $this->assertTrue(is_string(Type::arraylistDescription(
            array('maxLength' => 8)
        )));
        $this->assertTrue(is_string(Type::arraylistDescription(
            array(
                'allowedKeys' => array('company')
            )
        )));
    }

    function testEnum()
    {
        $options = array('values' => array('one' => 1, 'two' => 2, 'three' => 3, 'four' => 4));
        $this->assertEquals(2, Type::enum('two', 'test', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::enum('five', 'test', $options);
    }

    function testEnumEmpty()
    {
        $options = array('allowList' => true, 'values' => array('one' => 1, 'two' => 2, 'three' => 3, 'four' => 4));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::enum(array(), 'test', $options);
    }

    function testEnumAllowList()
    {
        $options = array('delimiter' => '/,\s*/', 'allowList' => true, 'values' => array('one' => 1, 'two' => 2, 'three' => 3, 'four' => 4));
        $this->assertEquals(array(2,3), Type::enum(array('two','three'), 'test', $options));
        $this->assertEquals(array(2,3), Type::enum('two,three', 'test', $options));
        $this->assertEquals(array(2,3), Type::enum('two, three', 'test', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        Type::enum('five', 'test', $options);
    }

    function testEnumDescription()
    {
        $this->assertTrue(is_string(Type::enumDescription(
            array(
                'allowList' => true, 'values' => array('one' => 1, 'two' => 2, 'three' => 3, 'four' => 4),
                'allowList' => true,
                'delimiter' => ','
            )
        )));
    }

}
