<?php
/**
 * Application Main Controller
 * 
 * @category Application
 */

namespace Application;

use Web\Mvc\ActionController;
use Web\Core\Registry;
use Web\Database\Table\Db;


class ApplicationController extends ActionController
{
    /**
     *
     * @var int
     */
    protected $_userType = 0;
       
    public function __construct()
    {
        $module = Registry::get('module');
        parent::__construct($module['session'], Db::MEMBER);
        if (! $this->isAjaxRequest()) {
            $this->plugin('head')->setTitlePrefix(Registry::get('siteName') . ' :: ');
            $this->setHeadTitle('Home');
            $this->layout->setLayout('index');
            $this->view->setVariable('showFormFlash', true);
            $this->plugin('tab')->set(1);
            $this->setTheme('chameleon');
            $this->view->setVariable('today', \Web\Utility\DateHelper::datepicker());
            if ($this->session->isLogged()) {
                $this->plugin('script')->addModuleBody('common');
            }
        }
        
        if ($this->session->isLogged()) {
            
        }
    }
    
    /**
     * get current user id
     * 
     * @return int
     */
    public function getUserId()
    {
        return $this->session->getId();
    }
    
    /**
     * get user type
     * 
     * @return int
     */
    public function getUserType()
    {
        return $this->_userType;
    }
    
    /**
     * Generate Paging
     * 
     * @param int $total 
     * @return StdClass 
     */
    protected function getPaging($total = 0, $limit = null, $generateLinks = true)
    {
        $paging = new Paging();
        $result = $paging->run($total, $limit, $generateLinks);
        $this->view->setVariable('paging', $result->html);
        $this->view->setVariable('pagingInfo', $result->info);
        $this->view->counter($result->sqlOffset);
        
        return $result;
    }
    
    /**
     * Action link cancel redirect
     */
    protected function cancelRedirect()
    {
        $this->redirect(ActionLinkHelper::getRedirectLink());
    }
    
    /**
     * access restricted message
     * 
     * @param string $url
     */
    protected function accessRestricted($url = '/')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    protected function _manager()
    {
        return $this->_isUserType(UserType::MANAGER);
    }
        
    protected function _staff()
    {
        return $this->_isUserType(UserType::STAFF);
    }
    
    protected function _auctioneer()
    {
        return $this->_isUserType(UserType::AUCTIONEER);
    }
    
    protected function _member()
    {
        return $this->_isUserType(UserType::MEMBER);
    }
    
    protected function _isUserType($type)
    {
        return $type == $this->_userType;
    }
}
