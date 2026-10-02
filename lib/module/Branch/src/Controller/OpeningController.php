<?php
/**
 * Branch Deduction Report Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;
use Branch\Form\Opening\CreateForm;
use Branch\Utility\{AdvanceWithdrawalStore, ManageAdvanceRepayment};

use Model\{RPTransMember, RPTransMemberDetails, CloseoutData, OpeningDetails, RPTransactionDetails, RPHeads, AdvanceWithdrawal, DeductionMemberDaily, DeductionMemberMonthly, InitialDeductionBalance};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;
use Shared\Form\AjaxForm;

class OpeningController extends BranchController
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
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_deductionMemberDailyModel = new DeductionMemberDaily();
        $this->_fmc_id = $this->_branch->id();
        $this->openingDetailsModel = new OpeningDetails();
        $this->closeoutDataModel = new CloseoutData();
        
    }
      
     /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function IndexAction()
    {
        $denominations = $this->get_currency_denominations();
        $form = new CreateForm($this->_fmc_id, $denominations);
        $today = date("Y-m-d");
        $sql = "SELECT * FROM ". $this->closeoutDataModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id}  and t.type='counted_till' and closeout_date ='{$today}'";
        $closeout_data = $this->closeoutDataModel->queryRows($sql);
        $counted_till = isset($closeout_data[0]->counted_till) ? $closeout_data[0]->counted_till : 0;
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    if(isset($closeout_data[0]->counted_till)){
                        $this->setFlashMessage('Opening Till Updated');
                    }else {
                         $this->setFlashMessage('Opening Till Created');
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
                'opening_date' => DateHelper::datepicker(),
            ];
            
            $form->populate($populate);
        }
       
        $sql = "SELECT * FROM ". $this->openingDetailsModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmc_id}  and opening_date ='{$today}'";
        $open_data = $this->openingDetailsModel->queryRows($sql); 
        $cashPrevYearOpening = $this->getCashOpening();
        $cashOpening = $this->getCashTransactions($cashPrevYearOpening);
        $this->registerForm($form);
        $this->registerPairs(['member', 'bank'], true);        
        $this->view->setVariable('currencyDenominations', $denominations);
        $this->view->setVariable('cashOpening', $cashOpening);
        $this->view->setVariable('openingData', $open_data);
        $this->view->setVariable('counted_till', $counted_till);
        $this->view->setVariable('closeoutData', $closeout_data);
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
            $fmc_id = $this->_branch->id();            
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
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
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
            $fmc_id = $this->_branch->id();
            $fy = $this->getFinancialYearInfo();
            $date = $fy['year'];
            $this->_startDate = $date;
            $today = date("Y-m-d");
            $prev_date = date('Y-m-d', strtotime($today .' -1 day'));
            $this->_endDate = $prev_date;
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
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
        $this->setHeadTitle('Opening Till');
        $this->setPageHeading('Opening Till');
        $this->setPageSubheading('');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Opening Till', $this->view->actionUrl('reports'));
        $breadrumb->add('');
    }
    
}
