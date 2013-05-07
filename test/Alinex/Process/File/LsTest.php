<?php

namespace Alinex\Proc\File;

class LsTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $pr = new Ls();
        $this->assertTrue((bool)$pr->getOutput());
    }

    function testSimple()
    {
        $pr = new Ls(__DIR__);
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAll();
        $this->assertEquals(3, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAlmostAll();
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->addTrailingSlash();
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->setSort('time');
        $pr->setTime('atime');
        $pr->sortReverse();
        $this->assertEquals(1, count($pr->getFiles()));
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    function testList()
    {
        $pr = new Ls(__DIR__);
        $pr->useLongFormat();
        error_log(print_r($pr->getFiles(), 1));
        error_log(print_r($pr->getMeta(), 1));
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAll();
        $this->assertEquals(3, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAlmostAll();
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->addTrailingSlash();
        $this->assertEquals(1, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->setSort('time');
        $pr->setTime('atime');
        $pr->sortReverse();
        $this->assertEquals(1, count($pr->getFiles()));
    }

}
