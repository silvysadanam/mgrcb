<?php
/**
 * Form CSRF handler
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Core\Server;
use Web\Core\Input;


class Csrf
{
    const TOKEN = '1Xz:0bK$je}_W93u2VqT-yL4=!';
    const FIELD = 'csrf';
    
    /**
     *
     * @var string 
     */
    private $_token;
    
    public function __construct()
    {
        $token = self::TOKEN . session_id();
        if (Server::is('HTTP_USER_AGENT')) {
            $token .= Server::get('HTTP_USER_AGENT');
        }
        $this->_token = sha1($token, false);
    }
    
    /**
     * verify CSRF token
     * 
     * @return boolean
     */
    public function verify() {
        $input = new Input();
        $valid = false;
        if ($input->isPost()) {
            if ($input->isPost(self::FIELD)) {
                $valid = 0 === strcmp($input->post(self::FIELD), $this->_token);
            }
        }
        
        return $valid;
    }
    
    /**
     * get CSRF hidden field
     * 
     * @return string
     */
    public function getField()
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', self::FIELD, $this->_token);
    }
    
    /**
     * get token
     * 
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }
    
    /**
     * verify CSRF token for ajax requests
     * 
     * @return boolean
     */
    public function verifyAjax()
    {
        $valid = false;
        $token = Server::get('HTTP_X_CSRF_TOKEN');
        if (! empty($token)) {
            $valid = 0 === strcmp($token, $this->_token);
        }
        
        return $valid;
    }
    
}
