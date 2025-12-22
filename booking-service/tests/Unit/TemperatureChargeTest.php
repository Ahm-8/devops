<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TemperatureChargeTest extends TestCase
{
    private function calculateCharge(float $temp, float $basePrice, float $targetTemp = 21): float
    {
        $difference = abs($temp - $targetTemp);
        
        if ($difference <= 2) {
            return 0;
        } elseif ($difference <= 5) {
            return $basePrice * 0.10;
        } elseif ($difference <= 10) {
            return $basePrice * 0.20;
        } elseif ($difference <= 20) {
            return $basePrice * 0.30;
        } else {
            return $basePrice * 0.50;
        }
    }

    public function test_temperature_charge_is_zero_percent_for_0_to_2_degree_difference(): void
    {
        $basePrice = 100;
        $targetTemp = 21;
        
        // 0°C difference
        $this->assertEquals(0.0, $this->calculateCharge(21, $basePrice, $targetTemp));
        
        // 1°C difference
        $this->assertEquals(0.0, $this->calculateCharge(20, $basePrice, $targetTemp));
        $this->assertEquals(0.0, $this->calculateCharge(22, $basePrice, $targetTemp));
        
        // 2°C difference
        $this->assertEquals(0.0, $this->calculateCharge(19, $basePrice, $targetTemp));
        $this->assertEquals(0.0, $this->calculateCharge(23, $basePrice, $targetTemp));
    }

    public function test_temperature_charge_is_10_percent_for_2_to_5_degree_difference(): void
    {
        $basePrice = 100;
        $targetTemp = 21;
        
        // 3°C difference
        $this->assertEquals(10.0, $this->calculateCharge(18, $basePrice, $targetTemp));
        $this->assertEquals(10.0, $this->calculateCharge(24, $basePrice, $targetTemp));
        
        // 5°C difference
        $this->assertEquals(10.0, $this->calculateCharge(16, $basePrice, $targetTemp));
        $this->assertEquals(10.0, $this->calculateCharge(26, $basePrice, $targetTemp));
    }

    public function test_temperature_charge_is_20_percent_for_5_to_10_degree_difference(): void
    {
        $basePrice = 100;
        $targetTemp = 21;
        
        // 6°C difference
        $this->assertEquals(20.0, $this->calculateCharge(15, $basePrice, $targetTemp));
        $this->assertEquals(20.0, $this->calculateCharge(27, $basePrice, $targetTemp));
        
        // 10°C difference
        $this->assertEquals(20.0, $this->calculateCharge(11, $basePrice, $targetTemp));
        $this->assertEquals(20.0, $this->calculateCharge(31, $basePrice, $targetTemp));
    }

    public function test_temperature_charge_is_30_percent_for_10_to_20_degree_difference(): void
    {
        $basePrice = 100;
        $targetTemp = 21;
        
        // 11°C difference
        $this->assertEquals(30.0, $this->calculateCharge(10, $basePrice, $targetTemp));
        $this->assertEquals(30.0, $this->calculateCharge(32, $basePrice, $targetTemp));
        
        // 20°C difference
        $this->assertEquals(30.0, $this->calculateCharge(1, $basePrice, $targetTemp));
        $this->assertEquals(30.0, $this->calculateCharge(41, $basePrice, $targetTemp));
    }

    public function test_temperature_charge_is_50_percent_for_20_plus_degree_difference(): void
    {
        $basePrice = 100;
        $targetTemp = 21;
        
        // 21°C difference
        $this->assertEquals(50.0, $this->calculateCharge(0, $basePrice, $targetTemp));
        $this->assertEquals(50.0, $this->calculateCharge(42, $basePrice, $targetTemp));
        
        // 30°C difference
        $this->assertEquals(50.0, $this->calculateCharge(-9, $basePrice, $targetTemp));
        $this->assertEquals(50.0, $this->calculateCharge(51, $basePrice, $targetTemp));
    }
}
