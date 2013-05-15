<?php

namespace Alinex\Process\File;

class MvTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '/../../../data/proc/';

    function testSimple()
    {
        $testdir = __DIR__.self::TESTDIR;
        // cleanup of old files
        $pr = new Rm(
            array(
                $testdir.'move1',
                $testdir.'move2',
                $testdir.'move3',
                $testdir.'move4',
                $testdir.'move5'
            )
        );
        $pr->exec();
        // create file to move
        $pr = new Touch($testdir.'move1');
        $pr->exec();
        if (!file_exists($testdir.'move1'))
            $this->markTestSkipped();
        // start test
        $pr = new Mv($testdir.'move1', $testdir.'move2');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists($testdir.'move1');
        $this->assertFileExists($testdir.'move2');
        $files = $pr->getFiles();
        $this->assertEquals(1, count($files));
        $this->assertEquals($testdir.'move1', $files[0]['from']);
        $this->assertEquals($testdir.'move2', $files[0]['to']);
        // cleanup
        $pr = new Rm($testdir.'move2');
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
        // create file to move
        $pr = new Touch(
            array($testdir.'move3', $testdir.'move4')
        );
        $pr->exec();
        $pr = new Mkdir(
            array($testdir.'move5')
        );
        $pr->exec();
        if (!file_exists($testdir.'move3')
            || !file_exists($testdir.'move4')
            || !file_exists($testdir.'move5'))
            $this->markTestSkipped();
        // start test
        $pr = new Mv(
            array($testdir.'move3', $testdir.'move4'),
            $testdir.'move5'
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileNotExists($testdir.'move3');
        $this->assertFileNotExists($testdir.'move4');
        $this->assertFileExists($testdir.'move5/move3');
        $this->assertFileExists($testdir.'move5/move4');
        $files = $pr->getFiles();
        $this->assertEquals(2, count($files));
        // cleanup
        $pr = new Rm($testdir.'move5');
        $pr->recursive()->exec();
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }
}
