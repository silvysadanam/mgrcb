<?php
/**
 * ControlCenter Login Form
 * 
 * @category ControlCenter
 * @package  Form
 */

namespace ControlCenter\Form;

use Web\Form\Form;
use Web\Core\Session;
use Web\Form\Captcha;


class AuthForm extends Form
{
    /**
     * max number of login attempts allowed
     */
    const MAX_ATTEMPTS = 3;
    const ATTEMPTS_SESSION = 'ControlCenterLoginAttempts';
    
    /**
     *
     * @var int 
     */
    private $_loginCount;
    
    /**
     * captcha instance
     * 
     * @var \Web\Form\Captcha 
     */
    private $_captcha;
    
    /**
     *
     * @var string 
     */
    private $_passcode = '0123454321#$!';
       
    /**
     *
     * @var boolean 
     */
    private $_captchaEnabled = true;
        
    public function __construct()
    {
        parent::__construct();
        $this->addField('passcode');
        $this->_loginCount = Session::get(self::ATTEMPTS_SESSION, 0);
        $this->_captcha = new Captcha();
    }
    
    /**
     * get Captcha instance
     * 
     * @return \Web\Form\Captcha
     */
    public function captcha()
    {
        return $this->_captcha;
    }
    
    /**
     * set Captcha enabled status
     * 
     * @param boolean $enabled
     */
    public function captchaEnabled($enabled = true)
    {
        $this->_captchaEnabled = (bool) $enabled;
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        if ($this->_loginCount > self::MAX_ATTEMPTS) {
            die('Unauthorized access, retry limit exceeded');
        }
        if ($this->_captchaEnabled) {
            if (! $this->_captcha->isValid()) {
                $this->error->invalid('Verification code');
            }
        }
        if ($this->noError()) {
            $this->postback();
            if ($this->_passcode != $this->getValue('passcode')) {
                $this->error->invalid('Auth Key');
            }
            if ($this->hasError()) {
                ++$this->_loginCount;
                Session::set(self::ATTEMPTS_SESSION, $this->_loginCount);
            }
        }
        
        return $this->noError();
    }
    
}
