<?php

namespace Alinex\Process\File;

class RmTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '/../../../data/proc/';

    function testInitial()
    {
        $pr = new Rm();
        $this->assertEmpty($pr->getOutput());
    }

    /**
     * @depends testInitial
     */
    function testSimple()
    {
        $testdir = __DIR__.self::TESTDIR;
        // create file to remove
        $pr = new Touch($testdir.'rm1');
        $pr->exec();
        if (!file_exists($testdir.'rm1'))
            $this->markTestSkipped();
        // start test
        $pr = new Rm($testdir.'rm1');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists($testdir.'rm1');
        $files = $pr->getFiles();
        $this->assertEquals(1, count($files));
        $this->assertEquals($testdir.'rm1', $files[0]);
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testInitial
     */
    function testMultiple()
    {
        $testdir = __DIR__.self::TESTDIR;
        // create file to remove
        $pr = new Touch(
            array($testdir.'rm2', $testdir.'rm3')
        );
        $pr->exec();
        if (!file_exists($testdir.'rm2')
            || !file_exists($testdir.'rm3'))
            $this->markTestSkipped();
        // start test
        $pr = new Rm(
            array($testdir.'rm2', $testdir.'rm3')
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists($testdir.'rm2');
        $this->assertFileNotExists($testdir.'rm3');
        $files = $pr->getFiles();
        $this->assertEquals(2, count($files));
        $this->assertEquals($testdir.'rm2', $files[0]);
        $this->assertEquals($testdir.'rm3', $files[1]);
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testRecursive()
    {
        $testdir = __DIR__.self::TESTDIR;
        // prepare test
        $pr = new Mkdir($testdir.'rm4/rm5/rm6');
        $pr->createParents()->exec();
        if (!file_exists($testdir.'rm4/rm5/rm6'))
            $this->markTestSkipped();
        // run the test
        $pr = new Rm($testdir.'rm4');
        $pr->recursive()->exec();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists($testdir.'rm4/rm5/rm6');
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

}
