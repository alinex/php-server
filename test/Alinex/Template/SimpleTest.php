<?php

namespace Alinex\Template;

class SimpleTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num}. test', array('num' => 1))
        );
    }

    function testTrim()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num|trim}. test', array('num' => '    1     '))
        );
    }

    function testDump()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num|dump}. test', array('num' => 1))
        );
        $this->assertEquals(
            'Test [1, 2, 5] done',
            Simple::run('Test {num|dump} done', array('num' => array(1,2,5)))
        );
    }

    function testPrintf()
    {
        $this->assertEquals(
            'A 01. test',
            Simple::run('A {num|printf %02d.} test', array('num' => 1))
        );
    }

}
