<?php
/**
 * Application CommonAjax Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Form\AjaxForm;

use Model\{Member, Fmc};


class CommonAjaxController extends ApplicationController
{
    /**
     * 
     * @var \Web\Mvc\AjaxResponse
     */
    private $_response;
    
    /**
     * 
     * @var \Application\Form\AjaxForm
     */
    private $_form;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        if (! $this->isAjaxRequest()) {
            exit;
        }
        
        $this->_form = new AjaxForm();
        if (! $this->_form->isPost()) {
            exit;
        }
        
        $this->_response = $this->getAjaxResponse();
        $this->_response->message('Invalid request');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        return $this->getViewModel();
    }
    
    /**
     * member name action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberNameAction()
    {
        $this->_form->addField('code');
        $values = $this->_form->run();
        $name = '';
        $code = '';
        
        if ('' != $values['code']) {
            $memberModel = new Member();
            if ($memberModel->checkByColumn('code', $values['code'])) {
                $valid = true;
                $member = $memberModel->selectByColumn('code', $values['code']);
                if ($this->isManager()) {
                    $valid = $this->_branch->id() == $member->fmc_id;
                } elseif ($values['id'] > 0) {
                    $fmcModel = new Fmc();
                    if ($fmcModel->hasId($values['id'])) {
                        $valid = $member->fmc_id == $values['id'];
                    } else {
                        $valid = false;
                    }
                }

                if ($valid) {
                    $name = $member->name;
                    $code = $member->code;
                }
            }
        }
        
        $this->_response->addData($name, 'name')
                        ->addData($code, 'code')
                        ->message('success')
                        ->valid();
        
        return $this->getViewModel();
    }
    
    /**
     * fmc filter action
     * 
     * @return \Web\View\ViewModel
     */
    public function fmcFilterAction()
    {
        $values = $this->_form->run();
        $options = [0 => '--ALL--'];
        if ($values['id'] > 0) {
            $fmcModel = new Fmc();
            if ($fmcModel->hasId($values['id'])) {
                $memberModel = new Member();
                $sql = $memberModel->getSql();
                $sql->whereId('fmc_id', $values['id']);
                $where = $sql->getWhere();
                if ($memberModel->selectTotal($where) > 0) {
                    $list = $memberModel->selectFields(['id', 'code', 'name'], $where, 'code');
                    foreach ($list as $item) {
                        $options[$item->id] = $item->code . ' - ' . $item->name;
                    }
                }
            }
        }

        $this->_response->addData($this->view->formHelper('select', $options), 'html')
                        ->message('success')
                        ->valid();
        
        return $this->getViewModel();
    }
    
}
