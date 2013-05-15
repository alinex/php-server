<?php

namespace Alinex\Process\File;

class MkdirTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '/../../../data/proc/';

    function testSimple()
    {
        $testdir = __DIR__.self::TESTDIR;
        // cleanup
        $pr = new Rm(
            array(
                $testdir.'mkdir1',
                $testdir.'mkdir2',
                $testdir.'mkdir3',
                $testdir.'mkdir4'
            )
        );
        $pr->recursive()->exec();
        // run test
        $pr = new Mkdir($testdir.'mkdir1');
        $pr->exec();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'mkdir1');
        $files = $pr->getDirectories();
        $this->assertEquals(1, count($files));
        $this->assertEquals($testdir.'mkdir1', $files[0]);
        // cleanup
        $pr = new Rm($testdir.'mkdir1');
        $pr->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testMultiple()
    {
        $testdir = __DIR__.self::TESTDIR;
        $pr = new Mkdir(
            array($testdir.'mkdir2', $testdir.'mkdir3')
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'mkdir2');
        $this->assertFileExists($testdir.'mkdir3');
        $files = $pr->getDirectories();
        $this->assertEquals(2, count($files));
        $this->assertEquals($testdir.'mkdir2', $files[0]);
        $this->assertEquals($testdir.'mkdir3', $files[1]);
        // cleanup
        $pr = new Rm(
            array($testdir.'mkdir2', $testdir.'mkdir3')
        );
        $pr->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testParents()
    {
        $testdir = __DIR__.self::TESTDIR;
        $pr = new Mkdir($testdir.'mkdir4/mkdir5/mkdir6');
        $pr->createParents();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'mkdir4/mkdir5/mkdir6');
        $files = $pr->getDirectories();
        $this->assertEquals(3, count($files));
        $this->assertEquals($testdir.'mkdir4', $files[0]);
        $this->assertEquals($testdir.'mkdir4/mkdir5', $files[1]);
        $this->assertEquals($testdir.'mkdir4/mkdir5/mkdir6', $files[2]);
        // cleanup
        $pr = new Rm($testdir.'mkdir4');
        $pr->recursive()->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

}
