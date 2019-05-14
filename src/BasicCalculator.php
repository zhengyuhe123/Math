<?php
namespace Math;

class BasicCalculator
{
    public static function ceil($number)
    {
        if (strpos($number, '.') !== false) {
            if (preg_match("~\.[0]+$~", $number)) return bcround($number, 0);
            if ($number[0] != '-') return bcadd($number, 1, 0);
            return bcsub($number, 0, 0);
        }
        return $number;
    }
    
    public static function floor($number)
    {
        if (strpos($number, '.') !== false) {
            if (preg_match("~\.[0]+$~", $number)) return bcround($number, 0);
            if ($number[0] != '-') return bcadd($number, 0, 0);
            return bcsub($number, 1, 0);
        }
        return $number;
    }
    
    public static function round($number, $precision = 0)
    {
        if (strpos($number, '.') !== false) {
            if ($number[0] != '-') return bcadd($number, '0.' . str_repeat('0', $precision) . '5', $precision);
            return bcsub($number, '0.' . str_repeat('0', $precision) . '5', $precision);
        }
        return $number;
    }
}

