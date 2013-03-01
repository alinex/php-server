<?php

namespace Alinex\Util;

class StringTest extends \PHPUnit_Framework_TestCase
{

    protected $_string = 'This is an Teststring!';

    public function testStartsWith()
    {
        $this->assertTrue(String::startsWith($this->_string, 'This'));
        $this->assertFalse(String::startsWith($this->_string, 'this'));
        $this->assertFalse(String::startsWith($this->_string, 'is'));
        $this->assertTrue(String::startsWith($this->_string, ''));
    }

    public function testEndsWith()
    {
        $this->assertTrue(String::endsWith($this->_string, '!'));
        $this->assertFalse(String::endsWith($this->_string, 'an'));
        $this->assertTrue(String::endsWith($this->_string, ''));
    }

    public function testDump()
    {
        $this->assertEquals('null', String::dump(null), 'null value');
        $this->assertEquals('12', String::dump(12), 'numeric value');
        $this->assertEquals('true', String::dump(true), 'boolean value');
        $this->assertEquals('false', String::dump(false), 'boolean value');
        $this->assertEquals('"I am"', String::dump("I am"), 'string value');
        $this->assertEquals('"I\'m"', String::dump("I'm"), 'string value with quotes');
        $this->assertEquals('"Say "Hello""', String::dump("Say \"Hello\""), 'string value with double quotes');
        $this->assertEquals('[1, 2, 3]', String::dump(array(1,2,3)), 'list value');
        $this->assertEquals('["one" => 1, "two" => 2, "three" => 3]', String::dump(array('one' => 1, 'two' => 2, 'three' => 3)), 'list value');
        $ex = new \Exception();
        $this->assertEquals('[Exception]', String::dump($ex), 'list value');
    }

    public function testPregMask()
    {
        $this->assertEquals('test \\/', String::pregMask('test /'));
        $this->assertEquals('test \\(', String::pregMask('test ('));
        $this->assertEquals('test \\)', String::pregMask('test )'));
        $this->assertEquals('test \\-', String::pregMask('test -'));
        $this->assertEquals('test \\[', String::pregMask('test ['));
        $this->assertEquals('test \\]', String::pregMask('test ]'));
        $this->assertEquals('test \\^', String::pregMask('test ^'));
        $this->assertEquals('test \\$', String::pregMask('test $'));
        $this->assertEquals('test \\.', String::pregMask('test .'));
        $this->assertEquals('test \\*', String::pregMask('test *'));
        $this->assertEquals('test \\?', String::pregMask('test ?'));
        $this->assertEquals('test \\{', String::pregMask('test {'));
        $this->assertEquals('test \\}', String::pregMask('test }'));
    }

    public function testWordbreak()
    {
        $text = <<<EOT
Linux was originally developed as a free operating system for Intel x86-based personal computers. It has since been ported to more computer hardware platforms than any other operating system. It is a leading operating system on servers and other big iron systems such as mainframe computers and supercomputers:[13][14][15][16] more than 90% of today's 500 fastest supercomputers run some variant of Linux,[17] including the 10 fastest.[18] Linux also runs on embedded systems (devices where the operating system is typically built into the firmware and highly tailored to the system) such as mobile phones, tablet computers, network routers, televisions[19][20] and video game consoles; the Android system in wide use on mobile devices is built on the Linux kernel.
The development of Linux is one of the most prominent examples of free and open source software collaboration: the underlying source code may be used, modified, and distributed—commercially or non-commercially—by anyone under licenses such as the GNU General Public License. Typically Linux is packaged in a format known as a Linux distribution for desktop and server use. Some popular mainstream Linux distributions include Debian (and its derivatives such as Ubuntu and Linux Mint), Fedora (and its derivatives such as Red Hat Enterprise Linux and CentOS), Mandriva/Mageia, OpenSUSE, and Arch Linux. Linux distributions include the Linux kernel, supporting utilities and libraries and usually a large amount of application software to fulfill the distribution's intended use.
A distribution oriented toward desktop use will typically include the X Window System and an accompanying desktop environment such as GNOME or KDE Plasma. Some such distributions may include a less resource intensive desktop such as LXDE or Xfce for use on older or less powerful computers. A distribution intended to run as a server may omit all graphical environments from the standard install and instead include other software such as the Apache HTTP Server and an SSH server such as OpenSSH. Because Linux is freely redistributable, anyone may create a distribution for any intended use. Applications commonly used with desktop Linux systems include the Mozilla Firefox web browser, the LibreOffice office application suite, and the GIMP image editor.
Since the main supporting user space system tools and libraries originated in the GNU Project, initiated in 1983 by Richard Stallman, the Free Software Foundation prefers the name GNU/Linux.
EOT;
        $final = <<<EOT
Linux was originally developed as a free operating system for Intel
x86-based personal computers. It has since been ported to more computer
hardware platforms than any other operating system. It is a leading
operating system on servers and other big iron systems such as mainframe
computers and supercomputers:[13][14][15][16] more than 90% of today's 500
fastest supercomputers run some variant of Linux,[17] including the 10
fastest.[18] Linux also runs on embedded systems (devices where the
operating system is typically built into the firmware and highly tailored
to the system) such as mobile phones, tablet computers, network routers,
televisions[19][20] and video game consoles; the Android system in wide use
on mobile devices is built on the Linux kernel.
The development of Linux is one of the most prominent examples of free and
open source software collaboration: the underlying source code may be used,
modified, and distributed—commercially or non-commercially—by anyone
under licenses such as the GNU General Public License. Typically Linux is
packaged in a format known as a Linux distribution for desktop and server
use. Some popular mainstream Linux distributions include Debian (and its
derivatives such as Ubuntu and Linux Mint), Fedora (and its derivatives
such as Red Hat Enterprise Linux and CentOS), Mandriva/Mageia, OpenSUSE,
and Arch Linux. Linux distributions include the Linux kernel, supporting
utilities and libraries and usually a large amount of application software
to fulfill the distribution's intended use.
A distribution oriented toward desktop use will typically include the X
Window System and an accompanying desktop environment such as GNOME or KDE
Plasma. Some such distributions may include a less resource intensive
desktop such as LXDE or Xfce for use on older or less powerful computers. A
distribution intended to run as a server may omit all graphical
environments from the standard install and instead include other software
such as the Apache HTTP Server and an SSH server such as OpenSSH. Because
Linux is freely redistributable, anyone may create a distribution for any
intended use. Applications commonly used with desktop Linux systems include
the Mozilla Firefox web browser, the LibreOffice office application suite,
and the GIMP image editor.
Since the main supporting user space system tools and libraries originated
in the GNU Project, initiated in 1983 by Richard Stallman, the Free
Software Foundation prefers the name GNU/Linux.
EOT;
        $this->assertEquals($final, String::wordbreak($text));

    }

    public function testConvertType()
    {
        $this->assertEquals(gettype('test'), gettype(String::convertType('test')));
        $this->assertEquals(gettype(5), gettype(String::convertType('5')));
        $this->assertEquals(gettype(5.3), gettype(String::convertType('5.3')));
        $this->assertEquals(gettype(true), gettype(String::convertType('true')));
    }

    public function testEscape()
    {
        $this->assertEquals('\\n', String::escape("\n"));
    }

}
