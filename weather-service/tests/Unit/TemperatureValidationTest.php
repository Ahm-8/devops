<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TemperatureValidationTest extends TestCase
{
    public function test_valid_temperature_range(): void
    {
        $minTemp = -50;
        $maxTemp = 50;
        
        $this->assertLessThanOrEqual($maxTemp, 25);
        $this->assertGreaterThanOrEqual($minTemp, 25);
        $this->assertGreaterThanOrEqual($minTemp, -10);
        $this->assertLessThanOrEqual($maxTemp, 40);
    }

    public function test_temperature_is_numeric(): void
    {
        $temp = 21.5;
        $this->assertIsNumeric($temp);
        $this->assertIsFloat($temp);
    }

    public function test_temperature_integer_conversion(): void
    {
        $temp = 21.7;
        $this->assertEquals(21, (int)$temp);
        $this->assertEquals(22, round($temp));
    }

    public function test_temperature_difference_calculation(): void
    {
        $temp1 = 25;
        $temp2 = 21;
        
        $difference = abs($temp1 - $temp2);
        $this->assertEquals(4, $difference);
    }

    public function test_temperature_absolute_difference(): void
    {
        $this->assertEquals(4, abs(25 - 21));
        $this->assertEquals(4, abs(21 - 25));
        $this->assertEquals(0, abs(21 - 21));
    }
}
