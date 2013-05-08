<?php

namespace Alinex\Proc\File;

class TouchTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '../../../data/proc/';
    
    function testInitial()
    {
        $pr = new Touch(self::TESTDIR.'file1');
        $this->assertTrue($pr->isSuccess());        
        $this->assertTrue(file_exists(self::TESTDIR.'file1'));
        $pr = new Touch(self::TESTDIR.'file2');
        $pr->setTime(time()-6000);
        $this->assertTrue($pr->isSuccess());        
        $pr = new Touch(self::TESTDIR.'file3');
        $pr->copyTimeFrom(self::TESTDIR.'file2');
        $this->assertTrue($pr->isSuccess());        
        // cleanup
        $pr = new Rm(self::TESTDIR.'file1');
        $pr->exec();
        $pr = new Rm(self::TESTDIR.'file2');
        $pr->exec();
        $pr = new Rm(self::TESTDIR.'file3');
        $pr->exec();        
    }

}
