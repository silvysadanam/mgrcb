<?php
/**
 * Branch Deduction Report Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */
namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\Opening\CreateCloseoutForm;

use Model\{RPTransMember, RPTransMemberDetails, CloseoutDetails, CloseoutData, OpeningDetails, RPTransactionDetails, RPHeads, AdvanceWithdrawal, DeductionMemberDaily, DeductionMemberMonthly, InitialDeductionBalance};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;
use Web\Utility\FormHelper;
use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;
use Shared\Form\AjaxForm;

class CloseoutController extends ApplicationController
{  
    /**
     *
     * @var \Model\DeductionMemberDaily
     */
    private $_model;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
        
    public function __construct()
    {
         parent::__construct();
        $this->loginRequired();
        $this->_model = new CloseoutData();
        $this->_rmodel = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_fmc_id = FormHelper::MF_ACCOUNT_ID;
        $this->openingDetailsModel = new OpeningDetails();
        $this->_sql = $this->_model->getSql();
       // $this->closeoutDataModel = new CloseoutData();
        $this->closeoutDetailsModel = new CloseoutDetails();
        
    }
    
      
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list();
        $this->_breadcrumb('List');
        return $this->getViewModel();
    }
    
    /**
     * load list
     * 
     * @param boolean $active
     */
    private function _list($active = false)
    {
        $filters = new FilterForm();
        $fields = ['from_date', 'to_date'];        
        $filters->addFields($fields);
        $filters->addFilter('from_date', $filters->getDateFilter());
        $filters->addFilter('to_date', $filters->getDateFilter());
      //  $filters->addFilter('receiptNo', $filters->getMaxTwoFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, $active);
        $where = $this->_sql->getWhere();       
        
        //$where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        if (!empty($values['from_date']) && !empty($values['to_date'])){
            $queryTotal .= ' WHERE ' . $where. " and `l`.fmc_id =  $this->_fmc_id";
        } else {
            $queryTotal .= " WHERE `l`.fmc_id =  $this->_fmc_id";
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`type`, `l`.`expected_till`, `l`.`closeout_date`, `l`.`counted_2`, `l`.`counted_till`, `l`.`till_difference`'
                   . 'FROM  `' . $this->_model->getTable() . '` `l` ';
             if (!empty($values['from_date']) && !empty($values['to_date'])){
                $query .= ' WHERE ' . $where . " and `l`.`type` ='cash' and `l`.fmc_id =  $this->_fmc_id";
            } else {
                $query .= " where `l`.fmc_id = $this->_fmc_id and `l`.`type` ='cash'";
            }
            $query .= ' ORDER BY `l`.`closeout_date` desc LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        
        $this->view->setVariable('total', $total);
       // $this->view->setVariable('voucher_type', $values['voucher_type']);
    }
    
    /**
     * apply filters
     * 
     * @param array $values
     * @param boolean $active
     */
    private function _applyFilters($values, $active = false)
    {
        $memberFilter = true;
        /*if (isset($values['from_date'])) {
            if ('' != $values['from_date']) {
                $this->_sql->whereGreater('closeout_date', DateHelper::fromDatepicker($values['from_date']));
            }
        }
        if (isset($values['to_date'])) {
            if ('' != $values['to_date']) {
                $this->_sql->whereLess('closeout_date', DateHelper::fromDatepicker($values['to_date']));
            }
        }*/
        
         if (!empty($values['from_date']) && !empty($values['to_date'])){
              $this->_sql->whereBetween('closeout_date', DateHelper::fromDatepicker($values['from_date']), DateHelper::fromDatepicker($values['to_date']));
         }
        
    }
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->_getId();
        $sql = "SELECT * FROM ". $this->_model->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id} and `t`.type ='cash' and `t`.ID ={$id}";
        $closeout_data = $this->_model->queryRows($sql);
        $counted_till = isset($closeout_data[0]->counted_till) ? $closeout_data[0]->counted_till : 0;
        $sql = "SELECT * FROM ". $this->_model->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id} and `t`.type ='counted_till' and `t`.closeout_date ='{$closeout_data[0]->closeout_date}'";
        $opening_till_data = $this->_model->queryRows($sql);
        
        $this->view->setVariable('closeOutData', $closeout_data);        
        $this->view->setVariable('openingData', $opening_till_data);

        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * get  id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
                $valid = true;
            }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'advance');
        }
        
        return $id;
    }
     /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function CreateAction()
    {
        $cashComputed = 0;
        $denominations = $this->get_currency_denominations();
        $form = new CreateCloseoutForm($this->_fmc_id, $denominations);
        $today = date("Y-m-d");
        $sql = "SELECT * FROM ". $this->_model->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id} and `t`.type ='cash' and `t`.closeout_date ='{$today}'";
        $closeout_data = $this->_model->queryRows($sql);
        $counted_till = isset($closeout_data[0]->counted_till) ? $closeout_data[0]->counted_till : 0;
        $sql = "SELECT * FROM ". $this->_model->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id} and `t`.type ='counted_till' and `t`.closeout_date ='{$today}'";
        $opening_till_data = $this->_model->queryRows($sql);
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    if(isset($closeout_data[0]->counted_till)){
                        $this->setFlashMessage('Closeout Data Updated');
                        $this->redirect('closeout' . $id);
                    }else {
                         $this->setFlashMessage('Closeout Completed');
                         $this->redirect('closeout' . $id);
                    }
                    $this->redirect('opening' . $id);
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
                } catch (\ErrorException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                }
            }
        } else {
            $populate = [
                'closeout_date' => DateHelper::datepicker(),
            ];
            
            $form->populate($populate);
        }
        $sql = "SELECT * FROM ". $this->closeoutDetailsModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id} and closeout_date ='{$today}'";
        $closeout_details = $this->closeoutDetailsModel->queryRows($sql); 
        
        $cashPrevYearOpening = $this->getCashOpening();
        $cashComputed = $this->getCashTransactions($cashPrevYearOpening);
        $this->registerForm($form);
        $this->registerPairs(['member', 'bank'], true);        
        $this->view->setVariable('currencyDenominations', $denominations);
        $this->view->setVariable('total_computed', $cashComputed);
        $this->view->setVariable('closeOutDetails', $closeout_details);        
        $this->view->setVariable('counted_till', $counted_till);
        $this->view->setVariable('opening_till_data', $opening_till_data);
        $this->view->setVariable('closeout_till_data', $closeout_data);
        $this->addModuleScript('opening/create');
        $this->addModuleScript('opening/parseDecimalNumber');
        
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    
    private function getFinancialYearInfo()
    {
    /*
        $today = date("Y-m-d");
        $date=date_create($today);

      //  echo "<br> Month: ".date_format($date,"m");
        if (date_format($date,"m") >= 4) {//On or After April (FY is current year - next year)
            $financial_year = (date_format($date,"Y")) . '-' . (date_format($date,"y")+1);
            $currentYear = (date_format($date,"Y"));
        } else {//On or Before March (FY is previous year - current year)
            $financial_year = (date_format($date,"Y")-1) . '-' . date_format($date,"y");
            $currentYear = (date_format($date,"Y")-1);
        }
        $currentFinancialYearWithDate = $currentYear."-04-01";
        //echo "<br> FY ".$financial_year;
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);*/
        
           //$yrdata= strtotime($this->_startDate);
        //echo date('m', $yrdata);
        $today = date("Y-m-d");
        $date=date_create($today);
        //echo date_format($date,"m");
      //  echo "<br> Month: ".date_format($date,"m");
        if (date_format($date,"m") >= 4) {//On or After April (FY is current year - next year)
            $financial_year = (date_format($date,"Y")) . '-' . (date_format($date,"y")+1);
            $currentYear = (date_format($date,"Y"));
        } else {//On or Before March (FY is previous year - current year)
            $financial_year = (date_format($date,"Y")-1) . '-' . date_format($date,"y");
            $currentYear = (date_format($date,"Y")-1);
        }
        $currentFinancialYearWithDate = $currentYear."-04-01";
        //echo "<br> FY ".$financial_year;
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);
    }
    
    private function getCashOpening($date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id ;            
            $fy = $this->getFinancialYearInfo();
            
            $date = $fy['year'];
            $opb =0;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`head_name` = 'Cash' and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);  
            if(isset($data[0]->opb)){
                $opb = $data[0]->opb;
            }
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_rmodel->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`status` = '1' and `m`.`transDate` < '$date' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $cashPayment =  $data[0]->sum;
        //Cash payment ends here
            return round(($cashTotal - $cashPayment),2);
    }
    
    private function getCashTransactions($opb)
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id ;
            $fy = $this->getFinancialYearInfo();
            $date = $fy['year'];
            $this->_startDate = $date;
            $today = date("Y-m-d");
            $this->_endDate = $today;
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_rmodel->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $cashPayment =  $data[0]->sum;
        //Cash payment ends here
            return round(($cashTotal - $cashPayment),2);
    }
    public function get_currency_denominations(){
            $denominations = array(
                    'five_hundreds'=>500,
                    'two_hundreds'=>200,
                    'hundreds'=>100,
                    'fifties'=>50,
                    'twenties'=>20,
                    'tens'=>10,
                    'fives'=>5,
                    'twos'=>2,
                    'ones'=>1,
            );
            return $denominations;
	}

    
    /**
     * set breadcrumb
     * 
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('Closeout');
        $this->setPageHeading('Closeout');
        $this->setPageSubheading('');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Closeout', $this->view->actionUrl('reports'));
        $breadrumb->add('');
    }
    
}
