<?php
/**
 * Application Login Form
 * 
 * @category Application
 * @package  Application\Form
 */

namespace Application\Form;

use Web\Form\Form;
use Web\Security\Password;
use Web\Core\Session;
use Web\Form\Captcha;


class LoginForm extends Form
{
    const MAX_ATTEMPTS = 3;
    const ATTEMPTS_SESSION = 'UserLoginAttempts';
    
    /**
     *
     * @var int 
     */
    private $_loginCount;
    
    /**
     *
     * @var \Web\Form\Captcha 
     */
    private $_captcha;
    
    /**
     *
     * @var string 
     */
    private $_passwordColumn = 'access_key';
    
    /**
     *
     * @var boolean 
     */
    private $_captchaEnabled = true;
    
    private $_user;
        
    public function __construct()
    {
        parent::__construct();
        
        //echo Password::encrypt('fwsmfadmin247'); exit;
        $fields = [
            'credential11', 
            'credential22',
        ];
        $this->addFields($fields);
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
     * set captcha enabled status
     * 
     * @param boolean $enabled
     */
    public function captchaEnabled($enabled = true)
    {
        $this->_captchaEnabled = (bool) $enabled;
    }
    
    /**
     * check captcha enabled
     * 
     * @return boolean
     */
    public function isCaptchaEnabled()
    {
        return $this->_captchaEnabled;
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
            $username = $this->getValue('credential11');
            $password = $this->getValue('credential22');
            if ($this->validator->isNull($username)) {
                $this->error->required('Username / Password');
            } elseif ($this->validator->isNull($password)) {
                $this->error->required('Username / Password');
            } else {
                if ($this->getModel()->checkByColumn('username', $username)) {
                    $this->_user = $this->getModel()->selectByColumn('username', $username);
                    if (Password::match($password, $this->_user->{$this->_passwordColumn})) {
                        Session::clear(self::ATTEMPTS_SESSION);
                    } else {
                        $this->error->invalid('Username / Password 2');
                    }
                } else {
                    $this->error->invalid('Username / Password 1');
                }
            }
            
            if ($this->hasError()) {
                ++$this->_loginCount;
                Session::set(self::ATTEMPTS_SESSION, $this->_loginCount);
            }
        }
        
        return $this->noError();
    }
    
    /**
     * get user
     * 
     * @return \stdClass
     */
    public function getUser()
    { 
        return $this->_user;
    }
    
}
