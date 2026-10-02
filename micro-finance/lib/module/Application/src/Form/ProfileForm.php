<?php
/**
 * Application Profile Form
 * 
 * @category Application
 * @package  Application\Form
 */

namespace Application\Form;

use Web\Form\Form;
use Web\Security\Password;


class ProfileForm extends Form
{
    /**
     *
     * @var string 
     */
    private $_credentialColumn = 'access_key';
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'name',
            'phone',
            'profile_password1', 
            'profile_password2',
            'profile_password3',
        ];
        $this->addFields($fields);
        $this->trackChanges();
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $values = $this->postback();
        if ($this->validator->isNull($values['name'])) {
            $this->error->required('Name');
        }
        if ($this->validator->isNull($values['phone'])) {
            $this->error->required('Phone');
        } elseif (1 == preg_match('/[^\d\+\(\)\s,-]/', $values['phone'])) {
            $this->error->invalid('Phone');
        }
        
        $passwordChange = false;
        if ($this->validator->notNull($values['profile_password2'])
            || $this->validator->notNull($values['profile_password3'])) {
            $passwordChange = true;
            if ($this->validator->isNull($values['profile_password1'])) {
                $this->error->required('Current Password');
            }
            if ($this->validator->isNull($values['profile_password2'])) {
                $this->error->required('New Password');
            } elseif ($this->validator->isNull($values['profile_password3'])) {
                $this->error->required('Repeat New Password');
            } elseif ($values['profile_password2'] != $values['profile_password3']) {
                $this->error->noMatch('New Password', 'Repeat New Password');
            } elseif (! $this->validator->password($values['profile_password2'])) {
                $this->error->noFormat('New Password');
            }
        }
        
        if ($this->noError() && $passwordChange) {
            $credential = $this->getModel()->selectColumnById($this->_credentialColumn, $this->getId());
            if (! Password::match($values['profile_password1'], $credential)) {
                $oldPassword = $this->getModel()->selectColumnById('import_password', $this->getId());
                if ('' != $oldPassword) {
                    if (! Password::old($values['profile_password1'], $oldPassword)) {
                        $this->error->incorrect('Current Password');
                    }
                } else {
                    $this->error->incorrect('Current Password');
                }
            }
        }

        return $this->noError();
    }
    
    /**
     * update profile
     * 
     * @return boolean|array
     */
    public function change()
    {
        $modified = $this->getModified();
        if (empty($modified)) {
            return false;
        }
        
        $result = [ 'password' => false ];
        $update = [];
        if (isset($modified['profile_password2'])) {
            $result['password'] = true;
            $update[$this->_credentialColumn] = Password::encrypt($modified['profile_password2']);
        }
        if (isset($modified['name'])) {
            $update['name'] = $modified['name'];
        }
        if (isset($modified['phone'])) {
            $update['phone'] = $modified['phone'];
        }
        
        if (empty($update)) {
            return false;
        }
        
        $this->save($update);
        
        return $result;
    }

}
