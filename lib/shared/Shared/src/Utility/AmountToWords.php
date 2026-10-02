<?php
/**
 * Shared AmountToWords Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;


class AmountToWords
{
    const MAX_DIGITS = 9;
    
    const CRORE = 'crore';
    const LAKH = 'lakh';
    const THOUSAND = 'thousand';
    const HUNDRED = 'hundred';
    
    private $_valid = false;
    private $_converted = [];
    private $_amount;
    
    private $_places = [];
    
    private $_options = [];
    
    private $_appendOnly;
    
    public function __construct($appendOnly = false)
    {
        $this->_appendOnly = $appendOnly;
    }
    
    public function convert($amount)
    {
        $this->_amount = (string) $amount;
        $this->_loadOptions();
        $this->_check();
        if ('0' == $this->_amount) {
            return ucfirst($this->_options[0]);
        }
        
        $this->_split();
        
        if (isset($this->_places[self::CRORE])) {
            $this->_converted[] = $this->_word($this->_places[self::CRORE]) . ' ' . self::CRORE;
        }
        
        if (isset($this->_places[self::LAKH])) {
            $this->_converted[] = $this->_word($this->_places[self::LAKH]) . ' ' . self::LAKH;
        }
        
        if (isset($this->_places[self::THOUSAND])) {
            $this->_converted[] = $this->_word($this->_places[self::THOUSAND]) . ' ' . self::THOUSAND;
        }
        
        if (isset($this->_places[self::HUNDRED])) {
            if ($this->_places[self::HUNDRED] > 99) {
                $hundreds = intdiv($this->_places[self::HUNDRED], 100);
                $this->_converted[] = $this->_options[$hundreds] . ' ' . self::HUNDRED;
            }
            
            $tens = $this->_places[self::HUNDRED] % 100;
            if ($tens > 0) {
                $this->_converted[] = $this->_word($tens);
            }
        }
        
        if ($this->_appendOnly) {
            $this->_converted[] = 'only';
        }
        
        return ucfirst(implode(' ', $this->_converted));
    }
    
    private function _check()
    {
        if ('' == $this->_amount) {
            return;
        }
        
        if (0 == preg_match('/^[1-9]\d*$/', $this->_amount)) {
            return;
        }
        
        if (strlen($this->_amount) > self::MAX_DIGITS) {
            return;
        }
        
        $this->_valid = true;
    }
    
    private function _loadOptions()
    {
        $this->_options = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleone',
            12 => 'tweleve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
        ];
    }
    
    private function _split()
    {
        if (strlen($this->_amount) < 4) {
            $this->_places[self::HUNDRED] = (int) ltrim($this->_amount, '0');
            return;
        }
        
        $hundereds = (int) ltrim(substr($this->_amount, -3), '0');
        if ($hundereds > 0) {
            $this->_places[self::HUNDRED] = $hundereds;
        }
        
        $reduced = substr($this->_amount, 0, -3);
        
        $thousands = (int) ltrim(substr($reduced, -2), '0');
        if ($thousands > 0) {
            $this->_places[self::THOUSAND] = $thousands;
        }
        
        if (strlen($reduced) < 3) {
            return;
        }
        
        $reduced2 = substr($reduced, 0, -2);
        $lakhs = (int) ltrim(substr($reduced2, -2), '0');
        
        if ($lakhs > 0) {
            $this->_places[self::LAKH] = $lakhs;
        }
        
        $reduced3 = (int) ltrim(substr($reduced2, 0, -2));
        if ($reduced3 > 0) {
            $this->_places[self::CRORE] = $reduced3;
        }
    }
    
    private function _word($number)
    {
        if (isset($this->_options[$number])) {
            return $this->_options[$number];
        }
        
        $quotient = intdiv($number, 10);
        $tens = $quotient * 10;
        $word = $this->_options[$tens];
        $ones = $number % 10;
        if (0 == $ones) {
            return $word;
        }
        
        return $word . ' ' . $this->_options[$ones];
    }
    
}
