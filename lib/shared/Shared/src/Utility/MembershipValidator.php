<?php
/**
 * Shared Membership Validator
 * 
 * @category Backend
 * @package  Backend\Form
 */

namespace Shared\Utility;


class MembershipValidator
{
    public function __construct()
    {
    }
    
    public function mobile($value)
    {
        if ($this->_noValue($value)) {
            return true;
        }
        
        if (! $this->_length($value, 10)) {
            return false;
        }
        
        return 1 == preg_match('/^[6-9]\d+$/', $value);
    }
    
    public function aadhar($value)
    {
        if ($this->_noValue($value)) {
            return true;
        }
        
        if (false === strpos($value, ' ')) {
            if (! $this->_length($value, 12)) {
                return false;
            }
            
            return 1 == preg_match('/^\d\d+$/', $value);
        }
        
        $cleaned = preg_replace('/\s+/', '', $value);
        if (! $this->_length($cleaned, 12)) {
            return false;
        }
        
        if (0 == preg_match('/^\d\d+$/', $cleaned)) {
            return false;
        }
        
        $parts = explode(' ', $cleaned);
        if (3 != count($parts)) {
            return false;
        }
        
        $valid = true;
        foreach ($parts as $item) {
            if (4 != strlen($item)) {
                $valid = false;
                break;
            }
        }
        
        return $valid;
    }
    
    public function horsePower($value)
    {
        if ($this->_noValue($value)) {
            return true;
        }
        
        if (! is_numeric($value)) {
            return false;
        }
        
        return $value > 0.0;
    }
    
    public function accountNumber($value)
    {
        $digits = strlen($value);
        if ($digits < 11 || $digits > 16) {
            return false;
        }
        
        return 1 == preg_match('/^[A-Z0-9][A-Z0-9]+$/', $value);
    }
    
    public function isNull($value)
    {
        return $this->_noValue($value);
    }
    
    public function notSelected($value)
    {
        return 0 == $value;
    }
    
    public function password($value)
    {
        $valid = false;
        $length = strlen($value);
        if ($length >= 6 && $length <= 20) {
            $valid = 1 == preg_match('/^[^[:space:]]+$/', $value);
        }
        
        return $valid;
    }
    
    
    private function _noValue($value)
    {
        return '' == trim($value);
    }

    private function _length($value, $length)
    {
        return $length == strlen($value);
    }
}
