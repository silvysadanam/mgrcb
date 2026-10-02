<?php
/**
 * Application Login Form
 * 
 * @category Application
 * @package  Application\Form
 */

namespace Application\Form;

use Web\Form\Form;
use Web\Core\Session;
use Web\Form\Captcha;
use Web\Security\Password;
use Model\MemberPhoto;
use Web\Core\Registry;

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
    /**
     * 
     * @var \Model\MemberPhoto
     */
    private $_memberPhotoModel;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'credential11', 
            'credential22',
        ];
        $this->addFields($fields);
        $this->_loginCount = Session::get(self::ATTEMPTS_SESSION, 0);
        $this->_captcha = new Captcha();
        $this->_memberPhotoModel = new MemberPhoto();
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
        
        return $this->noError();
        /*
        if ($this->noError()) {
            $this->postback();
            $username = $this->getValue('credential11');
            $password = $this->getValue('credential22');
            if ($this->validator->isNull($username)) {
                $this->error->required('Username / Password');
            } elseif ($this->validator->isNull($password)) {
                $this->error->required('Username / Password');
            } else {
                $model = $this->getModel();
                $sql = $model->getSql();
                if ($this->validator->email($username)) {
                    $sql->whereEqual('email', $username);
                } elseif ($this->validator->mobile($username)) {
                    $sql->whereEqual('mobile_number', $username);
                } else {
                    $sql->whereEqual('user_code', $username);
                }
                
                //$sql->whereId('user_type_id', UserType::AUCTIONEER);
                $where = $sql->getWhere();
                
                if (1 == $model->selectTotal($where)) {
                    $this->_user = $model->selectRow($where);
                    if (! Password::match($password, $this->_user->{$this->_passwordColumn})) {
                        $this->error->invalid('Username / Password');
                    } elseif (0 == $this->_user->active) {
                        $this->error->add('Invalid Username / Password or inactive account');
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
         * 
         */
    }
    
    public function validateUser($username, $password)
    {
        $model = $this->getModel();
        $memberId = 0;
        $memberName = "";
        $memberFmc = 0;
        $memberMobile = '';
        $code = '';
        $status = 'success';
        $base = 'documents';
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
            if (! Password::match($password, $user->{$this->_passwordColumn})) {
                $status = 'invalid_access';
            } else {
                $sql = $this->_memberPhotoModel->getSql();
                $sql->whereId('member_id', $user->id);
                $where = $sql->getWhere();
                $photo = $this->_memberPhotoModel->selectRow($where);
                $memberId = $user->id;
                $memberName = $user->name;
                $memberFmc = $user->fmc_id;
                $code =  $user->code;
                if (is_object($photo) && isset($photo->file_path)) {
                     $url = $this->getRegistry('member_assets') . $base . '/'
                                . $photo->file_path . '/' . $photo->filename;
                } else {
                   $url = null; // or some default image path
                }
               
                $fileName = $url;
            }

            } else {
                 $status = 'invalid_access';
            }
         return ['status' => $status, 'memberId' => $memberId, 'memberName' => $memberName, 'fmc_id' => $memberFmc, 'memberMobile' =>$memberMobile, 'memberCode' => $code, 'photo' => $fileName];
    }
    
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['credential'])) {
            $errors['email'] = 'Code or Mobile Number';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
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
     /**
     * get registry value 
     * 
     * @param string $name
     * @return string 
     */
    protected function getRegistry($name)
    {
        return Registry::get($name);
    }
}
