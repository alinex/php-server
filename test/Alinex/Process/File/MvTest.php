<?php

namespace Alinex\Proc\File;

class MvTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '../../../data/proc/';

    function testSimple()
    {
        // cleanup of old files
        $pr = new Rm(
            array(
                self::TESTDIR.'move1',
                self::TESTDIR.'move2',
                self::TESTDIR.'move3',
                self::TESTDIR.'move4',
                self::TESTDIR.'move5'
            )
        );
        $pr->exec();          
        // create file to move
        $pr = new Touch(self::TESTDIR.'move1');
        $pr->exec();  
        if (!file_exists(self::TESTDIR.'move1'))
            $this->markTestSkipped();
        // start test
        $pr = new Mv(self::TESTDIR.'move1', self::TESTDIR.'move2');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists(self::TESTDIR.'move1');
        $this->assertFileExists(self::TESTDIR.'move2');
        $files = $pr->getFiles();
        error_log(print_r($files,1));
        $this->assertEquals(1, count($files));
        $this->assertEquals(self::TESTDIR.'move1', $files[0]['from']);
        $this->assertEquals(self::TESTDIR.'move2', $files[0]['to']);
        // cleanup
        $pr = new Rm(self::TESTDIR.'move2');
        $pr->exec();          
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }

    /**
     * @depends testSimple
     */
    function testMultiple()
    {
        // create file to move
        $pr = new Touch(
            array(self::TESTDIR.'move3', self::TESTDIR.'move4')
        );
        $pr->exec();  
        $pr = new Mkdir(
            array(self::TESTDIR.'move5')
        );
        $pr->exec();  
        if (!file_exists(self::TESTDIR.'move3') 
            || !file_exists(self::TESTDIR.'move4')
            || !file_exists(self::TESTDIR.'move5'))
            $this->markTestSkipped();
        // start test
        $pr = new Mv(
            array(self::TESTDIR.'move3', self::TESTDIR.'move4'),
            self::TESTDIR.'move5'
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists(self::TESTDIR.'move3');
        $this->assertFileNotExists(self::TESTDIR.'move4');
        $this->assertFileExists(self::TESTDIR.'move5/move3');
        $this->assertFileExists(self::TESTDIR.'move5/move4');
        $files = $pr->getFiles();
        $this->assertEquals(2, count($files));
        // cleanup
        $pr = new Rm(self::TESTDIR.'move5');
        $pr->recursive()->exec();          
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }
}
