<?php
/**
 * Branch Membership Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\{Tab, FlashMessage};
use Branch\Form\Membership\CreateForm;

use Model\{Member, Bank};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;

use Shared\Constant\MemberType;
use Shared\Form\FilterForm;
use Shared\Model\PairModel;
use Shared\Utility\{Membership, ManageMembership};


class MembershipController extends BranchController
{  
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Member();
        $this->currentTab(Tab::MEMBERSHIP);
        $this->setFlashMessageTitle('Member');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list();
        $this->_loadMemberTypes();
        $this->_breadcrumb('All');
        
        return $this->getViewModel();
    }
    
    /**
     * member action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberAction()
    {
        $this->_list(MemberType::LIFEMEMBER);
        $this->_breadcrumb('Members');
        
        return $this->getViewModel();
    }
    
    /**
     * auctioneer action
     * 
     * @return \Web\View\ViewModel
     */
    public function auctioneerAction()
    {
        $this->_list(MemberType::ORDMEMBER);
        $this->_breadcrumb('Auctioneers');
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $form = new CreateForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->_branch->id());
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('membership/view/id/' . $id);
                } catch (\PDOException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } catch (\Web\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } catch (\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                }
                $this->redirect('membership');
            }
        } else {
            $populate = [
                'member_type_id' => MemberType::LIFEMEMBER,
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadFormOptions();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('membership-create');
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->_getId();
        $membership = new Membership($id, false);
        $membership->load();
        $this->view->setVariable('membership', $membership);
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * change action
     * 
     * @return \Web\View\ViewModel
     */
    public function changeAction()
    {
        $id = $this->_getId();
        $membership = new Membership($id, false);
        $membership->load();
        $this->view->setVariable('membership', $membership);
        $this->_loadMemberTypes();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('document-validator');
        $this->addModuleScript('photo-validator');
        $this->addModuleScript('membership-change');
        $this->layout->addModal('membership/change');
        $this->_breadcrumb('Edit');
        
        return $this->getViewModel();
    }
    
    /**
     * manage action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageAction()
    {
        $id = $this->_getId();
        
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $membership = new ManageMembership($id, false);
        $result = $membership->run($this->getAjaxResponse(), $this->getParamId('action'));
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    
    /**
     * load list
     * 
     * @param int $list
     */
    private function _list($list = 0)
    {
        $values = [];
        if (0 == $list) {
            $filters = new FilterForm();
            $fields = ['type'];
            $filters->addFields($fields);
            $filters->numeric($fields);
            $values = $filters->getFilteredValues();
            $this->view->setVariable('filters', $filters);
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $this->_branch->id());
        
        if (MemberType::LIFEMEMBER == $list) {
            $sql->whereId('member_type_id', MemberType::LIFEMEMBER);
        } elseif (MemberType::ORDMEMBER == $list) {
            $sql->whereId('member_type_id', MemberType::ORDMEMBER);
        } elseif ($values['type'] > 0) {
            if (in_array($values['type'], [MemberType::LIFEMEMBER, MemberType::ORDMEMBER])) {
                $sql->whereId('member_type_id', $values['type']);
            }
        }
        
        $where = $sql->getWhere();

        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'name', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('list');
    }
    
    /**
     * load member types
     */
    private function _loadMemberTypes()
    {
        $types = [
            MemberType::LIFEMEMBER => 'Member',
            MemberType::ORDMEMBER => 'Auctioneer',
        ];
        
        LookupStore::register('type', $types);
    }
    
    /**
     * load form options
     */
    private function _loadFormOptions()
    {
        $pairModel = new PairModel();
        LookupStore::register('designationType', $pairModel->designationType());
        LookupStore::register('documentType', $pairModel->documentType());
        LookupStore::register('panchayathBrand', $pairModel->panchayathBrand());
        LookupStore::register('accountType', $pairModel->accountType());
    }
    
    /**
     * get member id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $valid = $this->_model->selectColumnById('fmc_id', $this->_branch->id());
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'membership');
        }
        
        return $id;
    }
    
    /**
     * set date limit
     */
    private function _setDateLimit()
    {
        $this->view->setVariable('startDate', '01/01/1941');
        $year = DateHelper::year() - 17;
        $this->view->setVariable('endDate', '31/12/' . $year);
    }
    
    /**
     * load banks
     */
    private function _loadBanks()
    {
        $bankModel = new Bank();
        if ($bankModel->selectTotal() > 0) {
            $list = $bankModel->select(null, 'bank');
            $banks = [];
            foreach ($list as $item) {
                $banks[$item->id] = $item->bank . ', ' . $item->branch . ' / ' . $item->ifsc;
            }
            LookupStore::register('bank', $banks);
            $this->view->setVariable('hasBanks', true);
            
        } else {
            $this->view->setVariable('hasBanks', false);
        }
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Membership - ' . $page);
        $this->setPageHeading('Membership');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Membership', $this->view->actionUrl('membership'));
        $breadrumb->add($page);
    }
    
}
