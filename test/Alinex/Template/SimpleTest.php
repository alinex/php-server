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

    function testUndefined()
    {
        $this->assertEquals(
            'A {num}. test',
            Simple::run('A {num}. test', array())
        );
    }

    function testPath()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num.one}. test', array('num' => array('one' => 1)))
        );
    }

    // optional / default value

    function testOptional()
    {
        $this->assertEquals(
            'A . test',
            Simple::run('A {num?}. test', array())
        );
    }

    // CONTROLS

    function testComment()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num|default 1}. {%comment}NOT DISPLAYED{%endcomment}test', array())
        );
    }

    function testIf()
    {
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if toc}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num == 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num == 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num != 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num != 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num <> 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num <> 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num >= 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num >= 2}successful{%endif} test', array('num' => 1))
        );

        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num => 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num => 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num <= 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num <= 0}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num > 0}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num > 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num < 2}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num < 1}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num and num2}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num and toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );        
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num & num2}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if num & toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num or toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if toc2 or toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num | toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
        $this->assertEquals(
            'A 1.  test',
            Simple::run('A {num}. {%if toc2 | toc}successful{%endif} test', array('num' => 1, 'num2' => 2))
        );
    }

    function testIfElse()
    {
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num}successful{%else}failure{%endif} test', array('num' => 1))
        );
    }
    function xtestIfElse()
    {
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if num}successful{%else}failure{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if toc}failure{%else}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if toc}failure{%elseif num}successful{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if toc}failure{%elseif num}successful{%else}failure{%endif} test', array('num' => 1))
        );
        $this->assertEquals(
            'A 1. successful test',
            Simple::run('A {num}. {%if toc}failure{%elseif toc2}failure{%else}successful{%endif} test', array('num' => 1))
        );
    }
    
    function testSet()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('{%set num}1{%endset}A {num}. test', array())
        );
    }


    // MODIFIER

    function testDefault()
    {
        $this->assertEquals(
            'A 1. test',
            Simple::run('A {num|default 1}. test', array())
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

    function testDate()
    {
        $start = 1362143511;
        $this->assertEquals(
            'It worked on 1362143511',
            Simple::run('It worked on {start}', array('start' => $start))
        );
        $this->assertEquals(
            'It worked on 2013-03-01T14:11:51+0100',
            Simple::run('It worked on {start|date}', array('start' => $start))
        );
        $this->assertEquals(
            'It worked on 2013-03-01T14:11:51+0100',
            Simple::run('It worked on {start|date Y-m-d\TH:i:sO}', array('start' => $start))
        );
        $this->assertEquals(
            'It worked on 2013-03-01T14:11:51+0100',
            Simple::run('It worked on {start|date ISO8601}', array('start' => $start))
        );
        $this->assertEquals(
            'It worked on Friday',
            Simple::run('It worked on {start|date l}', array('start' => $start))
        );
    }

    function testUpper()
    {
        $this->assertEquals(
            'A 01. TEST',
            Simple::run('A {num|printf %02d.} {name|upper}', array('num' => 1, 'name' => 'test'))
        );
    }

    function testLower()
    {
        $this->assertEquals(
            'A 01. test',
            Simple::run('A {num|printf %02d.} {name|lower}', array('num' => 1, 'name' => 'TEST'))
        );
    }

}
