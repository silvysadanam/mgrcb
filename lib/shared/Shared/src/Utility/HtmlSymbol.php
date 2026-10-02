<?php
/**
 * Shared HtmlSymbol Utility
 *
 * @category   Shared
 * @package    Shared\Utility
 */

namespace Shared\Utility;

class HtmlSymbol
{
    /**
     * get symbol by code
     * 
     * @param string $key
     * @return string
     */
    public static function get($key)
    {
        $symbol = '';
        switch (strtolower($key)) {
            case 'kg':
            case 'kilogram':
            case 'kilograms':
            case 'kilo_gram':
            case 'kilo':
            case 'kgs':
                $symbol = self::kilogram();
                break;
            
            case 'inr':
            case 'rupee':
            case 'rupees':
            case 'rs':
            case 'currency':
                $symbol = self::rupee();
                break;
            
            case 'percentage':
            case 'percent':
                $symbol = '%';
                break;
            
            default:
                break;
        }
        
        return $symbol;
    }
    
    /**
     * get kilogram symbol
     * 
     * @return string
     */
    public static function kilogram()
    {
        return 'kg';
    }
    
    /**
     * get rupee symbol
     * 
     * @return string
     */
    public static function rupee()
    {
        return '&#8377;';
    }
    
}
