<?php

namespace Alinex\Util;

class NumberTest extends \PHPUnit_Framework_TestCase
{

    public function testToUnit()
    {
        $this->assertEquals('45g', Number::toUnit(45, 'g'));
        $this->assertEquals('1000g', Number::toUnit(1000, 'g'));
        $this->assertEquals('8kg', Number::toUnit(8000, 'g'));
        $this->assertEquals('8t', Number::toUnit(8000000, 'g'));
        $this->assertEquals('8Mbit', Number::toUnit(8000000, 'bit'));
        $this->assertEquals('8Gbit', Number::toUnit(8200000000, 'bit'));
        $this->assertEquals('8.2Gbit', Number::toUnit(8200000000, 'bit', 1));
        $this->assertEquals('8.20Gbit', Number::toUnit(8200000000, 'bit', 2));
        $this->assertEquals('500mg', Number::toUnit(0.5, 'g'));
    }

    public function testToUnitLong()
    {
        $this->assertEquals('45g', Number::toUnit(45, 'g', 0, true));
        $this->assertEquals('1000g', Number::toUnit(1000, 'g', 0, true));
        $this->assertEquals('8 thousands g', Number::toUnit(8000, 'g', 0, true));
        $this->assertEquals('8t', Number::toUnit(8000000, 'g', 0, true));
        $this->assertEquals('8 millions bit', Number::toUnit(8000000, 'bit', 0, true));
        $this->assertEquals('8 billions bit', Number::toUnit(8200000000, 'bit', 0, true));
        $this->assertEquals('8.2 billions bit', Number::toUnit(8200000000, 'bit', 1, true));
        $this->assertEquals('8.20 billions bit', Number::toUnit(8200000000, 'bit', 2, true));
        $this->assertEquals('500 thousandths g', Number::toUnit(0.5, 'g', 0, true));
    }

    public function testNegativeUnit()
    {
        $this->assertEquals('-45g', Number::toUnit(-45, 'g'));
        $this->assertEquals('-1000g', Number::toUnit(-1000, 'g'));
        $this->assertEquals('-8kg', Number::toUnit(-8000, 'g'));
        $this->assertEquals('-8t', Number::toUnit(-8000000, 'g'));
        $this->assertEquals('-8Mbit', Number::toUnit(-8000000, 'bit'));
        $this->assertEquals('-8Gbit', Number::toUnit(-8200000000, 'bit'));
        $this->assertEquals('-8.2Gbit', Number::toUnit(-8200000000, 'bit', 1));
        $this->assertEquals('-8.20Gbit', Number::toUnit(-8200000000, 'bit', 2));
        $this->assertEquals('-500mg', Number::toUnit(-0.5, 'g'));
    }

    public function testToBinaryUnit()
    {
        $this->assertEquals('45B', Number::toBinaryUnit(45, 'B'));
        $this->assertEquals('1000B', Number::toBinaryUnit(1000, 'B'));
        $this->assertEquals('4KiB', Number::toBinaryUnit(4096, 'B'));
        $this->assertEquals('4MiB', Number::toBinaryUnit(4096*1024, 'B'));
    }

    public function testToTimerange()
    {
        $this->assertEquals('45 seconds', Number::toTimerange(45));
        $this->assertEquals('30 minutes', Number::toTimerange(1800));
        $this->assertEquals('1 day', Number::toTimerange(86400));
        $this->assertEquals('2 days', Number::toTimerange(86400*2));
        $this->assertEquals('1 month', Number::toTimerange(2629746));
        $this->assertEquals('1 year', Number::toTimerange(31556952));
        $this->assertEquals('23 days, 3 hours, 33 minutes, 20 seconds', Number::toTimerange(2000000));
    }

    public function testToTimerangeShorten()
    {
        $this->assertEquals('45 seconds', Number::toTimerange(45, true));
        $this->assertEquals('30 minutes', Number::toTimerange(1800, true));
        $this->assertEquals('24 hours', Number::toTimerange(86400, true));
        $this->assertEquals('48 hours', Number::toTimerange(86400*2, true));
        $this->assertEquals('30 days', Number::toTimerange(2629746, true));
        $this->assertEquals('12 months', Number::toTimerange(31556952, true));
        $this->assertEquals('23 days', Number::toTimerange(2000000, true));
    }
}
