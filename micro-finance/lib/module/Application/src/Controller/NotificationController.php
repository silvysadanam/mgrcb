<?php
/**
 * Application Notification Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;

use Model\{Notification, NotificationComment, Administrator};

use Web\Utility\DateHelper;
use Web\Form\Form;


class NotificationController extends ApplicationController
{
    /**
     *
     * @var \Model\Notification 
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::INTERFACE_NOTIFICATION);
        $this->_model = new Notification();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list('all', 'All');
        
        return $this->getViewModel();
    }
    
    /**
     * unread action
     * 
     * @return \Web\View\ViewModel
     */
    public function unreadAction()
    {
        $this->_list('unread', 'Unread');
        $this->layout->setScript('notification/index');
        
        return $this->getViewModel();
    }
    
    /**
     * read action
     * 
     * @return \Web\View\ViewModel
     */
    public function readAction()
    {
        $this->_list('read', 'Read');
        $this->layout->setScript('notification/index');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $valid = false;
        $id = $this->getParam('id', true);
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $valid = $this->getUserId() == $this->_model->selectColumnById('administrator_id', $id);
            }
        }
        if (! $valid) {
            $this->setFlashMessage('Invalid request', 'notification', 'error');
        }
        
        $commentModel = new NotificationComment();
        $form = new Form();
        $form->addField('comments');
        if ($form->isPost()) {
            $values = $form->postback();
            if ('' != $values['comments']) {
                $values['notification_id'] = $id;
                $values['created_at'] = DateHelper::timestamp();
                $commentModel->insert($values);
                $this->setFlashMessage('Comment added', 'notification/view/id/' . $id);
            }
        }
        
        $this->registerForm($form);
        
        $notification = $this->_model->find($id);
        if (0 == $notification->viewed) {
            $update = [
                'viewed' => 1,
                'viewed_at' => DateHelper::timestamp(),
            ];
            $this->_model->updateById($update, $id);
        }
        
        $this->view->setVariable('notification', $notification);
        
        $sql = $this->_model->getSql();
        $sql->whereId('notification_id', $id);
        $where = $sql->getWhere();
        $total = $commentModel->selectTotal($where);
        if ($total > 0) {
            $this->view->setVariable('comments', $commentModel->select($where, 'id DESC'));
        }
        
        $this->registerPairs(['fmc']);
        
        $administratorModel = new Administrator();
        $this->view->setVariable('creator', $administratorModel->selectFieldsById(['name', 'manager_type'], $notification->created_by));
        
        $this->setHeadTitle('Notifications - View');
        $this->setPageHeading('Notifications');
        $this->setPageSubheading('View');
        $this->_breadcrumb('View Notification');
        
        return $this->getViewModel();
    }
    
    /**
     * remove action
     * 
     * @return \Web\View\ViewModel
     */
    public function removeAction()
    {
        $valid = false;
        $id = $this->getParam('id', true);
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $valid = $this->getUserId() == $this->_model->selectColumnById('administrator_id', $id);
            }
        }
        if (! $valid) {
            $this->setFlashMessage('Invalid request', 'notification', 'error');
        }
  
        $this->_model->deleteById($id);
        $this->setFlashMessage('Notification deleted', 'notification');
        
        return $this->getViewModel();
    }
    
    
    /**
     * notification list
     * 
     * @param string $type
     * @param string $title
     */
    private function _list($type = 'index', $title = 'All')
    {
        $sql = $this->_model->getSql();
        $sql->whereId('administrator_id', $this->getUserId())
            ->whereId('site_id', $this->registry('site_id'));
        
        if ('read' == $type) {
            $sql->whereOne('viewed');
        } elseif ('unread' == $type) {
            $sql->whereZero('viewed');
        }
        
        $where = $sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'id DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('listTitle', $title);
        $this->addDatatableScripts();
        $this->addModuleScript('notification-list');
        $this->registerPairs(['fmc']);
        
        $this->setHeadTitle('Notifications - ' . $title);
        $this->setPageHeading('Notifications');
        $this->setPageSubheading($title);
        $this->_breadcrumb($title . ' Notifications');
    }

    /**
     * set page breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page = null)
    {
        $breadrumb = $this->plugin('breadcrumb');
        if (null !== $page) {
            $breadrumb->add($page);
        }
    }

}
