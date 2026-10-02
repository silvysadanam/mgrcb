<?php
/**
 * Web WebClient Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Exception;


class WebClient
{
    /**
     *
     * @var int 
     */
    private $_timeout = 30;
    
    /**
     *
     * @var int 
     */
    private $_maxRedirects = 5;
    
    /**
     *
     * @var string 
     */
    private $_useragent;
    
    /**
     *
     * @var string 
     */
    private $_cookieFile;
    
    /**
     *
     * @var string 
     */
    private $_referer;
    
    public function __construct()
    {
        if (! function_exists('curl_init')) {
            throw new Exception('curl extension required');
        }
        $this->_userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:56.0) Gecko/20100101 Firefox/56.0';
        $this->_cookieFile = STORAGE_DIR . '/temp/cookie.txt';
    }
    
    /**
     * set user-agent
     * 
     * @param string $useragent
     * @return $this
     */
    public function setUseragent($useragent)
    {
        $this->_userAgent = $useragent;
        
        return $this;
    }
    
    /**
     * set timeout
     * 
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = (int) $timeout;
        
        return $this;
    }
    
    /**
     * set cookie file
     * 
     * @param string $cookieFile
     * @return $this
     */
    public function setCookieFile($cookieFile)
    {
        $this->_cookieFile = $cookieFile;
        
        return $this;
    }
    
    /**
     * set max redirects
     * 
     * @param int $redirects
     * @return $this
     */
    public function setMaxRedirects($redirects)
    {
        $this->_maxRedirects = (int) $redirects;
        
        return $this;
    }
    
    /**
     * set referer
     * 
     * @param string $referer
     * @return $this
     */
    public function setReferer($referer)
    {
        $this->_referer = $referer;
        
        return $this;
    }

    /**
     * get page
     * 
     * @param string $url 
     * @return string|boolean
     */
    public function get($url)
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->_maxRedirects);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_useragent); 
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookieFile);
            if (! empty($this->_referer)) {
                curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
            } else {
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            }
            $response = curl_exec($ch);
        } catch (Exception $e) {
           echo 'HTTP request failed. ' . $e->getMessage();
        }
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ('200' == $responseCode) {
            return trim($response);
        }
        
        return false;
    }

}
