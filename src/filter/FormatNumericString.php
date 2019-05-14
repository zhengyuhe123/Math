<?php
namespace Math\Filter;

class FormatNumericString
{
    public function filter($value)
    {
        return is_string($value) && preg_match('/^\-?[\d]*\.?[\d]{1,28}$/', $value) ? rtrim(rtrim(bcadd($value, '0', 28), '0'), '.') : $value;
        
    }
}

