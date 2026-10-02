<?php
/**
 * Branch Impersonate Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;

use Model\{Administrator, Impersonate};

use Shared\Constant\ManagerType;

use Web\Utility\DateHelper;


class ImpersonateController extends BranchController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $code = $this->getParam('code');
        if (! $this->_validateCode($code)) {
            $this->_redirect();
        }
        
        $impersonateModel = new Impersonate();
        $now = DateHelper::timestamp();
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($impersonateModel->hasId($id)) {
                $log = $impersonateModel->find($id);
                if (0 == $log->used && $log->expiry > $now) {
                    $valid = $code == $log->code;
                }
            }
        }
        
        if (! $valid) {
            $this->_redirect();
        }
        
        $managerValid = false;
        $administratorModel = new Administrator();
        if ($administratorModel->hasId($log->manager_id)) {
            $manager = $administratorModel->find($log->manager_id);
            $managerValid = ManagerType::FMC == $manager->manager_type;
        }
        
        if (! $managerValid) {
            $this->_redirect();
        }
        
        $impersonateModel->updateById(['used' => 1, 'used_at' => $now], $id);
        $this->session->set($log->manager_id);
        $this->setFlashMessage('Logged in as "' . $manager->username . '"');
        $this->redirect();
        
        return $this->getViewModel();
    }
    
    private function _validateCode($code)
    {
        if ('' == $code) {
            return false;
        }
        
        if (32 != strlen($code)) {
            return false;
        }
        
        return 1 == preg_match('/^[a-f0-9][a-f0-9]+$/', $code);
    }
    
}
