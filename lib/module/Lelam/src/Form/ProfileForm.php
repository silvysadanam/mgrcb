<?php
/**
 * Lelam Profile Form
 * 
 * @category Lelam
 * @package  Lelam\Form
 */

namespace Lelam\Form;

use Web\Form\Form;
use Web\Security\Password;


class ProfileForm extends Form
{
    /**
     *
     * @var string 
     */
    private $_passwordColumn = 'access_key';
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
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
        $update = [
            $this->_passwordColumn => Password::encrypt($this->getValue('profile_password2')),
        ];
        
        return $this->save($update);
    }

}
