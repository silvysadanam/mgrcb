<?php
/**
 * Backend Profile Form
 * 
 * @category Backend
 * @package  Backend\Form
 */

namespace Backend\Form;

use Web\Form\Form;
use Web\Security\Password;


class ProfileForm extends Form
{
    /**
     *
     * @var string 
     */
    private $_passwordColumn = 'access_key';
    
    /**
     * 
     * @var boolean
     */
    private $_changePassword = false;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'name',
            'username',
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
        if ($this->validator->isNull($values['username'])) {
            $this->error->required('Username');
        } else {
            $length = strlen($values['username']);
            if ($length < 6 || $length > 20) {
                $this->error->invalid('Username');
            } elseif (0 == preg_match('/^[a-z0-9][a-z0-9]+$/i', $values['username'])) {
                $this->error->invalid('Username');
            } elseif ($this->getModel()->checkByColumnExceptId('username', $values['username'], $this->getId())) {
                $this->error->exists('Username');
            }
        }
        
        if ($this->validator->notNull($values['profile_password2']) || $this->validator->notNull($values['profile_password3'])) {
            if ($this->validator->isNull($values['profile_password1'])) {
                $this->error->enter('Current password');
            }
            if ($this->validator->isNull($values['profile_password2'])) {
                $this->error->enter('New password');
            }
            if ($this->validator->isNull($values['profile_password3'])) {
                $this->error->required('Repeat new password');
            }
            if ($values['profile_password2'] != $values['profile_password3']) {
                $this->error->noMatch('New password', 'Repeat new password');
            } elseif (! $this->validator->password($values['profile_password2'])) {
                $this->error->noFormat('New password');
            } elseif ($values['profile_password1'] == $values['profile_password2']) {
                $this->error->add('New password should be different from current password');
            }
            
            if ($this->noError()) {
                $currentPassword = $this->getModel()->selectColumnById($this->_passwordColumn, $this->getId());
                if (! Password::match($values['profile_password1'], $currentPassword)) {
                    $this->error->incorrect('Current Password');
                } else {
                    $this->_changePassword = true;
                }
            }
        }
        
        return $this->noError();
    }
    
    /**
     * update profile
     * 
     * @return boolean
     */
    public function change()
    {
        $modified = $this->getModified();
        if (empty($modified)) {
            return false;
        }
        
        $update = [];
        if (isset($modified['name'])) {
            $update['name'] = $modified['name'];
        }
        
        if (isset($modified['username'])) {
            $update['username'] = $modified['username'];
        }
        
        if ($this->_changePassword) {
            $update[$this->_passwordColumn] = Password::encrypt($this->getValue('profile_password2'));
        }
        
        if (empty($update)) {
            return false;
        }
        
        return $this->save($update);
    }

}
