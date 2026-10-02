<?php
/**
 *  Web View Support StatusLabelHelper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\Support
 */

declare(strict_types=1);

namespace Web\View\Support;


class StatusLabelHelper
{
    /**
     * 
     * @var array
     */
    private static $_options = ['Disabled', 'Active'];
    
    /**
     * 
     * @var array
     */
    private static $_default = ['Disabled', 'Active'];
    
    /**
     * get label
     * 
     * @param int $key
     * @return string
     */
    public static function get(int $key = 0)
    {
        if (isset(self::$_options[$key])) {
            return self::$_options[$key];
        }
    }
    
    /**
     * set label for option
     * 
     * @param int $key
     * @param string $label
     */
    public static function set(int $key, string $label)
    {
        self::$_options[$key] = $label;
    }
    
    /**
     * set options
     * 
     * @param array $options
     */
    public static function options(array $options)
    {
        self::$_options = $options;
    }
    
    /**
     * reset options to default
     */
    public static function reset()
    {
        self::$_options = self::$_default;
    }
    
    /**
     * enabled /disabled
     */
    public static function enabled()
    {
        self::$_options = ['Disabled', 'Enabled'];
    }
    
    /**
     * active / inactive
     */
    public static function active()
    {
        self::$_options = ['Inactive', 'Active'];
    }
    
    /**
     * valid / invalid
     */
    public static function valid()
    {
        self::$_options = ['Invalid', 'Valid'];
    }
    
    /**
     * active / expired
     */
    public static function expired()
    {
        self::$_options = ['Expired', 'Active'];
    }
    
}
