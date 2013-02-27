<?php

namespace Alinex\Validator;

class CodeTest extends \PHPUnit_Framework_TestCase
{
    function testPhpNamespace()
    {
        $this->assertTrue(is_string(Code::phpNamespaceDescription()));
        $this->assertTrue(is_string(Code::phpNamespace(__NAMESPACE__, 'current namespace')));
        $this->assertEquals('Alinex', Code::phpNamespace('Alinex', 'test'), 'simple');
        $this->assertEquals('Alinex\Validator', Code::phpNamespace('Alinex\Validator', 'test'), 'subnamespace');
        $this->assertEquals('Alinex\Validator\Code', Code::phpNamespace('Alinex\Validator\Code', 'test'), 'swith class');
        $this->assertEquals('Alinex\Validator\Code', Code::phpNamespace('\Alinex\Validator\Code', 'test'), 'with starting backslash');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::phpNamespace('_not_allowed', 'starting _ is not allowed');
    }

    function testPhpClass()
    {
        $this->assertTrue(is_string(Code::phpClassDescription()));
        $this->assertTrue(is_string(Code::phpClass(__CLASS__, 'current class')));
        $this->assertEquals('Alinex', Code::phpClass('Alinex', 'test'), 'simple');
        $this->assertEquals('Alinex\Validator', Code::phpClass('Alinex\Validator', 'test'), 'subnamespace');
        $this->assertEquals('Alinex\Validator\Code', Code::phpClass('Alinex\Validator\Code', 'test'), 'swith class');
        $this->assertEquals('Alinex\Validator\Code', Code::phpClass('\Alinex\Validator\Code', 'test'), 'with starting backslash');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::phpClass('_not_allowed', 'starting _ is not allowed');
    }

    function testPhpClassExists()
    {
        $options = array(
            'exists' => true
        );
        $this->assertTrue(is_string(Code::phpClassDescription($options)));
        $options = array(
            'exists' => true,
            'autoload' => true,
            'relative' => __NAMESPACE__
        );
        $this->assertTrue(is_string(Code::phpClassDescription($options)));
        $this->assertEquals('Alinex\Validator\Code', Code::phpClass('Alinex\Validator\Code', 'test', $options), 'exists');
        $this->assertEquals('Alinex\Validator\Code', Code::phpClass('Validator\Code', 'test', $options), 'relative');
        $this->assertEquals('Alinex\Validator\Code', Code::phpClass('Code', 'test', $options), 'relative');
        $this->assertEquals('Alinex\Util\ArrayStructure', Code::phpClass('Alinex\Util\ArrayStructure', 'test', $options), 'loading');
#        $this->setExpectedException('Alinex\Validator\Exception');
#        Code::phpClass('NotExisting', 'not existing class', $options);
    }

    function testCallable()
    {
        $this->assertTrue(is_string(Code::callableDescription(
            array('relative' => 'xxx', 'allowFunction' => true)
        )));
        $this->assertTrue(is_array(Code::callable('Alinex\Validator\Type::integer', 'test')), 'integer method');
        $this->assertTrue(is_array(Code::callable('Type::integer', 'test', array('relative' => __NAMESPACE__))), 'integer method');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::callable('_not_allowed', 'starting _ is not allowed');
    }

    function testCallableInvalidClass()
    {
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::callable('Alinex\Validator\Typppe::integer', 'not existing class');
    }

    function testCallableUndefinedMethod()
    {
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::callable('Alinex\Validator\Type::outteger', 'not existing method');
    }

    function testCallableUndefinedFunction()
    {
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::callable('outteger', 'starting _ is not allowed', array('allowFunction' => true));
    }

    function testPrintf()
    {
        $this->assertTrue(is_string(Code::printfDescription()));
        $this->assertEquals('Alinex', Code::printf('Alinex', 'test'), 'simple');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::printf(array(), 'no string given');
    }

    function testPrintfMinParameter()
    {
        $options = array('minParameter' => 1);
        $this->assertTrue(is_string(Code::printfDescription($options)));
        $this->assertEquals('This are %2d examples', Code::printf('This are %2d examples', 'test', $options), 'one parameter');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::printf('No parameters', 'too less parameters', $options);
    }

    function testPrintfMaxParameter()
    {
        $options = array('minParameter' => 1, 'maxParameter' => 1);
        $this->assertTrue(is_string(Code::printfDescription($options)));
        $options = array('maxParameter' => 1);
        $this->assertTrue(is_string(Code::printfDescription($options)));
        $this->assertEquals('This are %2d examples', Code::printf('This are %2d examples', 'test', $options), 'one parameter');
        $this->setExpectedException('Alinex\Validator\Exception');
        Code::printf('The %s have %2d too much', 'tooo much parameters', $options);
    }

    function testPrintfReplace()
    {
        $options = array('replace' => array(5));
        $this->assertTrue(is_string(Code::printfDescription($options)));
        $this->assertEquals('This are 05 examples', Code::printf('This are %02d examples', 'test', $options), 'one parameter');
    }

}
