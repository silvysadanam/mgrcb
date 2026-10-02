<?php
/**
 * Web Security Password
 *
 * @category   Web
 * @package    Web\Security
 */

namespace Web\Security;


class Password
{
    /**
     * match password
     * 
     * @param string $input
     * @param string $base
     * @return boolean
     */
    public static function match($input, $base)
    {
        return password_verify($input, $base);
    }
    
    /**
     * encrypt password
     * 
     * @param string $input
     * @return string
     */
    public static function encrypt($input)
    {
        return password_hash($input, PASSWORD_BCRYPT);
    }
    
}
