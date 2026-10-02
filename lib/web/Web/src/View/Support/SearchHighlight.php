<?php
/**
 * Web View SearchHighlight
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\Support
 */

namespace Web\View\Support;


class SearchHighlight
{
    /**
     *
     * @var array 
     */
    private static $_options = [];
    
    /**
     *
     * @var string 
     */
    private static $_replacement = '<span style="background-color:yellow">\\1</span>';

    /**
     * get highlighted string
     * 
     * @param string $name
     * @param string $string
     * @return string
     */
    public static function get($name, $string)
    {
        if (! empty($string)) {
            $string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
            if (isset(self::$_options[$name])) {
                if (1 == preg_match(self::$_options[$name], $string)) {
                    $string = preg_replace(self::$_options[$name], self::$_replacement, $string, 1);
                }
            }
        }
        
        return $string;
    }
    
    /**
     * add highlight option
     * 
     * @param string $name
     * @param string $search
     */
    public static function add($name, $search)
    {
        self::$_options[$name] = '/(' . quotemeta($search) . ')/i';
    }
    
    /**
     * set replacement
     * 
     * @param string $replace
     */
    public static function replace($replace)
    {
        self::$_replacement = $replace;
    }
    
}
