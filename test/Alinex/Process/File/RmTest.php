<?php

namespace Alinex\Proc\File;

class RmTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '../../../data/proc/';

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
        // create file to remove
        $pr = new Touch(self::TESTDIR.'rm1');
        $pr->exec();  
        if (!file_exists(self::TESTDIR.'rm1'))
            $this->markTestSkipped();
        // start test
        $pr = new Rm(self::TESTDIR.'rm1');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists(self::TESTDIR.'rm1');
        $files = $pr->getFiles();
        $this->assertEquals(1, count($files));
        $this->assertEquals(self::TESTDIR.'rm1', $files[0]);
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testInitial
     */
    function testMultiple()
    {
        // create file to remove
        $pr = new Touch(
            array(self::TESTDIR.'rm2', self::TESTDIR.'rm3')
        );
        $pr->exec();  
        if (!file_exists(self::TESTDIR.'rm2') 
            || !file_exists(self::TESTDIR.'rm3'))
            $this->markTestSkipped();
        // start test
        $pr = new Rm(
            array(self::TESTDIR.'rm2', self::TESTDIR.'rm3')
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists(self::TESTDIR.'rm2');
        $this->assertFileNotExists(self::TESTDIR.'rm3');
        $files = $pr->getFiles();
        $this->assertEquals(2, count($files));
        $this->assertEquals(self::TESTDIR.'rm2', $files[0]);
        $this->assertEquals(self::TESTDIR.'rm3', $files[1]);
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testRecursive()
    {        
        // prepare test
        $pr = new Mkdir(self::TESTDIR.'rm4/rm5/rm6');
        $pr->createParents()->exec();
        if (!file_exists(self::TESTDIR.'rm4/rm5/rm6'))
            $this->markTestSkipped();
        // run the test
        $pr = new Rm(self::TESTDIR.'rm4');
        $pr->recursive()->exec();
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists(self::TESTDIR.'rm4/rm5/rm6');
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }
    
}
