<?php

namespace Alinex\Proc\File;

class TouchTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '../../../data/proc/';
    
    function testInitial()
    {
        // cleanup
        $pr = new Rm(
            array(
                self::TESTDIR.'touch1',
                self::TESTDIR.'touch2',
                self::TESTDIR.'touch3'
            )
        );
        $pr->exec();
        // run test
        $pr = new Touch(self::TESTDIR.'touch1');
        $this->assertTrue($pr->isSuccess());        
        $this->assertFileExists(self::TESTDIR.'touch1');        
        // time
        $pr = new Touch(self::TESTDIR.'touch2');
        $pr->setTime(time()-6000);
        $this->assertTrue($pr->isSuccess());        
        $this->assertFileExists(self::TESTDIR.'touch2');        
        // copy time
        $pr = new Touch(self::TESTDIR.'touch3');
        $pr->copyTimeFrom(self::TESTDIR.'touch2');
        $this->assertTrue($pr->isSuccess());        
        $this->assertFileExists(self::TESTDIR.'touch3');        
        // cleanup
        $pr = new Rm(
            array(
                self::TESTDIR.'touch1',
                self::TESTDIR.'touch2',
                self::TESTDIR.'touch3'
            )
        );
        $pr->exec();
    }

    /**
     * @depends testInitial
     */
    function testMultiple()
    {
        $pr = new Touch(
            array(self::TESTDIR.'touch4', self::TESTDIR.'touch5')
        );
        $this->assertTrue($pr->isSuccess());        
        $this->assertFileExists(self::TESTDIR.'touch4');        
        $this->assertFileExists(self::TESTDIR.'touch5');        
        // cleanup
        $pr = new Rm(
            array(self::TESTDIR.'touch4', self::TESTDIR.'touch5')
        );
        $pr->exec();
    }
}
