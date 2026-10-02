<?php
/**
 * Web Security Token
 *
 * @category   Web
 * @package    Web\Security
 */

namespace Web\Security;

use Web\Utility\DateHelper;


class Token
{
    const SALT = 'lkj:mF-9PqG_xAjmsmgrBY74jiIpK4PHKRoJhh9QY=e7Dg|cbZgF80o$gBna!==';
    
    public function __construct()
    {
        
    }
    
    /**
     * is valid token
     * 
     * @param string $token
     * @param string $expiry
     * @return boolean
     */
    public function isValid($token, $expiry = null)
    {
        $valid = false;
        if (32 == strlen($token)) {
            $valid = 1 == preg_match('/^[a-f0-9]+$/', $token);
            if (null !== $expiry) {
                $valid = $this->notExpired($expiry);
            }
        }
        
        return $valid;
    }
    
    /**
     * generate new token
     * 
     * @param string $base
     * @return string
     */
    public function generate($base = null)
    {
        return md5(microtime() . serialize($base) . self::SALT);
    }
    
    /**
     * get expiry time
     * 
     * @param string $modifier
     * @return string
     */
    public function getExpiry($modifier)
    {
        $time = DateHelper::getDateTime();
        $time->modify($modifier);
        
        return $time->format(DateHelper::MYSQL_DATETIME);
    }
    
    /**
     * check expiry
     * 
     * @param string $time expiry time
     * @return boolean
     */
    public function notExpired($time)
    {
        $now = DateHelper::getDateTime();
        $expiry = DateHelper::getDateTime($time);
        
        return $expiry > $now;
    }
    
}
