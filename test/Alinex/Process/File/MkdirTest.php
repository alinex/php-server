<?php

namespace Alinex\Proc\File;

class MkdirTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '../../../data/proc/';

    function testSimple()
    {        
        // cleanup
        $pr = new Rm(
            array(
                self::TESTDIR.'mkdir1',
                self::TESTDIR.'mkdir2',
                self::TESTDIR.'mkdir3',
                self::TESTDIR.'mkdir4'
            )
        );
        $pr->recursive()->exec();
        // run test
        $pr = new Mkdir(self::TESTDIR.'mkdir1');
        $pr->exec();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists(self::TESTDIR.'mkdir1');
        $files = $pr->getDirectories();
        $this->assertEquals(1, count($files));
        $this->assertEquals(self::TESTDIR.'mkdir1', $files[0]);
        // cleanup
        $pr = new Rm(self::TESTDIR.'mkdir1');
        $pr->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testMultiple()
    {        
        $pr = new Mkdir(
            array(self::TESTDIR.'mkdir2', self::TESTDIR.'mkdir3')
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists(self::TESTDIR.'mkdir2');
        $this->assertFileExists(self::TESTDIR.'mkdir3');
        $files = $pr->getDirectories();
        $this->assertEquals(2, count($files));
        $this->assertEquals(self::TESTDIR.'mkdir2', $files[0]);
        $this->assertEquals(self::TESTDIR.'mkdir3', $files[1]);
        // cleanup
        $pr = new Rm(
            array(self::TESTDIR.'mkdir2', self::TESTDIR.'mkdir3')
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
        $pr = new Mkdir(self::TESTDIR.'mkdir4/mkdir5/mkdir6');
        $pr->createParents();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists(self::TESTDIR.'mkdir4/mkdir5/mkdir6');
        $files = $pr->getDirectories();
        $this->assertEquals(3, count($files));
        $this->assertEquals(self::TESTDIR.'mkdir4', $files[0]);
        $this->assertEquals(self::TESTDIR.'mkdir4/mkdir5', $files[1]);
        $this->assertEquals(self::TESTDIR.'mkdir4/mkdir5/mkdir6', $files[2]);
        // cleanup
        $pr = new Rm(self::TESTDIR.'mkdir4');
        $pr->recursive()->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

}
