<?php
/**
 * Lelam Login Form
 * 
 * @category Lelam
 * @package  Form
 */

namespace Lelam\Form;

use Web\Form\Form;
use Web\Security\Password;
use Web\Core\Session;
use Web\Form\Captcha;

use Shared\Constant\MemberType;


class LoginForm extends Form
{
    /**
     * max number of login attempts allowed
     */
    const MAX_ATTEMPTS = 3;
    const ATTEMPTS_SESSION = 'LelamLoginAttempts';
    
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
    private $_passwordColumn = 'access_key';
    
    /**
     * 
     * @var int
     */
    private $_memberId;
    
    /**
     *
     * @var boolean 
     */
    private $_captchaEnabled = true;
        
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'credential30', 
            'credential40',
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
            $username = $this->getValue('credential30');
            $password = $this->getValue('credential40');
            if ($this->validator->isNull($username)) {
                $this->error->required('Username / Password');
            } elseif ($this->validator->isNull($password)) {
                $this->error->required('Username / Password');
            } else {
                $model = $this->getModel();
                $user = null;
                if ($this->_isMobile($username)) {
                    if ($model->checkByColumn('mobile', $username)) {
                        $user = $model->selectByColumn('mobile', $username);
                    }
                } 
                
                if (empty($user)) {
                    if ($model->checkByColumn('code', $username)) {
                        $user = $model->selectByColumn('code', $username);
                    }
                }
                
                if (! empty($user)) {
                    if (MemberType::ORDMEMBER == $user->member_type_id) {
                        if (! Password::match($password, $user->{$this->_passwordColumn})) {
                            $this->error->invalid('Username / Password');
                        } else {
                            $this->_memberId = $user->id;
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
    
    /**
     * get user id
     * 
     * @return int
     */
    public function userId()
    {
        return $this->_memberId;
    }
    
    /**
     * check for mobile number format
     * 
     * @param type $mobile
     * @return boolean
     */
    private function _isMobile($mobile)
    {
        if (10 != strlen($mobile)) {
            return false;
        }
        
        return 1 == preg_match('/^[6-9]\d+$/', $mobile);
    }
    
}
