<?php

namespace Alinex\Process\File;

class LsTest extends \PHPUnit_Framework_TestCase
{
    const FILENUM = 5;

    function testInitial()
    {
        $pr = new Ls();
        $this->assertTrue((bool)$pr->getOutput());
    }

    /**
     * @depends testInitial
     */
    function testSimple()
    {
        $pr = new Ls(__DIR__);
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAll();
        $this->assertEquals(self::FILENUM+2, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAlmostAll();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->addTrailingSlash();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->setSort('time');
        $pr->setTime('atime');
        $pr->sortReverse();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testList()
    {
        $pr = new Ls(__DIR__);
        $pr->useLongFormat();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAll();
        $this->assertEquals(self::FILENUM+2, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->showAlmostAll();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->addTrailingSlash();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
        $pr = new Ls(__DIR__);
        $pr->setSort('time');
        $pr->setTime('atime');
        $pr->sortReverse();
        $this->assertEquals(self::FILENUM, count($pr->getFiles()));
    }

}
