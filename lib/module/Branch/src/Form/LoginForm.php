<?php
/**
 * Branch Login Form
 * 
 * @category Branch
 * @package  Form
 */

namespace Branch\Form;

use Web\Form\Form;
use Web\Security\Password;
use Web\Core\Session;
use Web\Form\Captcha;

use Shared\Constant\ManagerType;


class LoginForm extends Form
{
    /**
     * max number of login attempts allowed
     */
    const MAX_ATTEMPTS = 3;
    const ATTEMPTS_SESSION = 'BranchLoginAttempts';
    
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
    private $_identityColumn = 'username';
    
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
        
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'credential10', 
            'credential20',
        ];
        $this->addFields($fields);
        $this->_loginCount = Session::get(self::ATTEMPTS_SESSION, 0);
        $this->_captcha = new Captcha();
        
        //echo Password::encrypt('Change12345#'); exit;
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
            $username = $this->getValue('credential10');
            $password = $this->getValue('credential20');
            if ($this->validator->isNull($username)) {
                $this->error->required('Username / Password');
            } elseif ($this->validator->isNull($password)) {
                $this->error->required('Username / Password');
            } else {
                $model = $this->getModel();
                if ($model->checkByColumn($this->_identityColumn, $username)) {
                    $user = $model->selectByColumn($this->_identityColumn, $username);
                    if (ManagerType::FMC == $user->manager_type) {
                        if (! Password::match($password, $user->{$this->_passwordColumn})) {
                            $this->error->invalid('Username / Password');
                        }
                    } else {
                        $this->error->invalid('Username / Password');
                    }
                } else {
                    $this->error->invalid('Username / Password');
                }
            }
            if ($this->hasError()) {
                ++$this->_loginCount;
                Session::set(self::ATTEMPTS_SESSION, $this->_loginCount);
            }
        }
        
        return $this->noError();
    }
    
}
