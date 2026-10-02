<?php
/**
 * Backend Deduction Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Form\Deduction\{CreateForm};

use Model\{Fmc, RPHeads, RPBSHeads, DeductionType, RPAccountSettings, Member, RPTransactionDetails, RPTransMemberDetails, RPTransMember, LoanSource, Schedule};
use Backend\Utility\{ManageHeads, ManageBSHeads};
use Shared\Utility\AccountsHelper;
use Shared\Constant\DeductionConfig;
use Web\Utility\FormHelper;
use Web\View\Support\LookupStore;
use DateTime;

class AccountsController extends BackendController
{
    /**
     * 
     * @var \Model\RPHeads
     */
    private $_model;
    private $_deductionTypeModel;
    /**
     * 
     * @var \Model\Fmc
     */
    private $_fmcModel;
    private $_headModel;
    private $_accountSettingModel;
    private $_transactionModel;
    private $_membTrans;
    private $_startDate = '';
    private $_loanSource;
    private $_scheduleModel;
    private $_bsheadModel;
    private $_fmc_id;
    /**
     * 
     * @var string
     */
    private $_endDate = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::ACCOUNTSHEAD);
        $this->_headModel = new RPHeads();
        $this->_accountSettingModel = new RPAccountSettings();
        $this->_deductionTypeModel = new DeductionType();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_loanSource = new LoanSource();
        $this->_scheduleModel = new Schedule();
        $this->_bsheadModel = new RPBSHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->view->setVariable('fmc', $this->_fmcModel->selectPairs(['id', 'name'], null, 'name'));
        $this->view->setVariable('heads', $this->_headModel->selectDistinct('fmc_id'));
        $this->_breadcrumb('FMC');
        
        return $this->getViewModel();
    }
    
     /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function settingsAction()
    {
        $this->view->setVariable('fmc', $this->_fmcModel->selectPairs(['id', 'name'], null, 'name'));
        $this->view->setVariable('settings', $this->_accountSettingModel->selectDistinct('fmc_id'));
        $this->_breadcrumb('FMC');
        
        return $this->getViewModel();
    }
     /**
     * BSConfig action
     * 
     * @return \Web\View\ViewModel
     */
    public function bsconfigAction()
    {
         $this->view->setVariable('fmc', $this->_fmcModel->selectPairs(['id', 'name'], null, 'name'));
        $this->view->setVariable('heads', $this->_bsheadModel->selectDistinct('fmc_id'));
        $this->_breadcrumb('FMC');
        
        return $this->getViewModel();
    }
      public function bsresetAction()
    {
        $this->view->setVariable('fmc', $this->_fmcModel->selectPairs(['id', 'name'], null, 'name'));
        $fyQuery = "SELECT fmc_id, MAX(fy) AS latest_fy from" . $this->_bsheadModel->getTableQuoted()." GROUP BY fmc_id";
        $fy = $this->_bsheadModel->queryRows($fyQuery);
        $fyMap = [];
        foreach ($fy as $row) {
            $fyMap[$row->fmc_id] = $row->latest_fy;
        }
        $fYear = $this->getFinancialYearInfo();
        $this->view->setVariable('currentFY', $fYear['FY']);
        $this->view->setVariable('fy', $fyMap);
        $this->_breadcrumb('Close Financial Year');
        
        return $this->getViewModel();
    }
     private function getFinancialYearInfo()
    {
        $today = date("Y-m-d");
        $date=date_create($this->_startDate);
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
    public function commonHeads()
    {
        $heads = array(
                    array('Salary', 'Direct Expense','salary'),
                    array('Salary Contribution', 'Direct Expense','sal_contribution'),
                    array('Provident Fund', 'Direct Expense','pf'),
                    array('Gratuity', 'Direct Expense','gratuity'),
                    array('Electricity charges', 'Direct Expense','elect_charge'),
                    array('Telephone charges', 'Direct Expense','tc'),
                    array('Printing & Stationery', 'Direct Expense','ps'),
                    array('Travelling expense', 'Direct Expense','travel_exp'),
                    array('Honorarium', 'Direct Expense','hon'),
                    array('Meeting expense', 'Direct Expense','me'),
                    array('Cleaning/Watchman', 'Direct Expense','cleaning'),
                    array('Repair & Maintenance', 'Direct Expense','rep_main'),
                    array('Furniture / Other Asset', 'Direct Expense','furn_other_asset'),
                    array('Allowance', 'Direct Expense','allowance'),
                    array('Rent', 'Direct Expense','rent'),
                    array('Subsidy', 'Direct Expense','subsidy'),
                    array('Sitting fees', 'Direct Expense','sf'),
                    array('General meeting expense', 'Direct Expense','gme'),
                    array('Other expense', 'Direct Expense','oe'),
                    array('Donation', 'Direct Expense','donation'),
                    array('Catching prizes', 'Direct Expense','cp'),
                    array('Tea expense', 'Direct Expense','tea_exp'),
                    array('Scholarship', 'Direct Expense','scholar'),
                    array('Medical aid', 'Direct Expense','med_aid'),
                    array('X mas gift', 'Direct Expense','xmasgift'),
                    array('Union(Rice)', 'Direct Expense','union_rice'),
                    array('Water Charges', 'Direct Expense','wc'),
                    array('Bank Charges/Cess', 'Direct Expense','bank_charge'),
                    array('Out side loan Paid', 'Direct Expense','outside_loan'),
                    array('Interest Paid outside', 'Direct Expense','int_paid_out'),
                    array('Rice Bonus', 'Direct Expense','rice_bonus'),
                    array('Fixed Assets', 'Direct Expense','fixed_assets'),
                    array('Liquidated assets', 'Direct Expense','liquidated_assets'),
                    array('Security Deposits', 'Direct Expense','exp_security_deposits'),
                    array('Security Deposits', 'Direct Income','security_deposits'),
                    array('Insurance', 'Direct Expense','insurance'),
                    array('Out side loan Paid', 'Direct Income','outside_loan'),
                    array('Bulk Loans', 'Direct Income','bulk_loans'),   
                    array('Bulk Loans', 'Direct Expense','exp_bulk_loans'),   
                    array('Excess Interest Received for UBL', 'Direct Income','excess_interest_received_UBL'),   
                    array('Excess Interest Received for MFL', 'Direct Income','excess_interest_received_MFL'),
                    array('Excess Interest Received for IOB', 'Direct Income','excess_interest_received_IOB'),
                    array('Excess Interest Received for CBL', 'Direct Income','excess_interest_received_CBL'),            
                    array('Interest Adjustment to UBL', 'Direct Expense','excess_interest_paid_UBL'),   
                    array('Interest Adjustment to MFL', 'Direct Expense','excess_interest_paid_MFL'),
                    array('Interest Adjustment to IOB', 'Direct Expense','excess_interest_paid_IOB'),
                    array('Interest Adjustment to CBL', 'Direct Expense','excess_interest_paid_CBL'),
                   // array('Union Contribution', 'Direct Expense','union_cont') ,
                   // array('FWS Contribution', 'Direct Expense','fws_cont') 
                );
        return $heads;
    }
    
    public function commonBSHeads()
    {
        $heads = array(
                    array('FWS loan repayment', 'Liability','FWS_loan_repayment'),
                    array('Advance Receieved', 'Liability','Advance_Receieved'),
                    array('Short-term loan/External debt', 'Liability','External_Debt'),
                    array('Deposits', 'Liability','Deposits'),
                    array('Security Deposits', 'Liability','Security_Deposits'),
                    array('Amount Payable', 'Liability','Amount_Payable'),
                    array('Microfinance Loan', 'Liability','Micro_loan_Liable'),
                    array('Union Bank Loan', 'Liability','Union_Bank_Loan_Liable'),
                    array('Costal Bank Loan', 'Liability','Costal_Bank_Loan_Liable'),
                    array('Indian Overseas Bank Loan', 'Liability','IOB_Loan_Liable'),
                    array('Bad debts to be written off', 'Liability','Bad_Debs_Written_off') ,
                    array('Bank Loan Collection', 'Liability','Bank_Loan_Collection'),
                    array('Insurance', 'Liability','Insurance'),
                    array('FMC Bulk Loan', 'Liability','FMC_Bulk_Loan'),
                    array('Branch Union', 'Liability','Branch_Union'),
                    array('FMC Loan Repayable', 'Assets','FMC_Loan_rep'),
                    array('Advance Paid', 'Assets','FMC_Advance'),
                    array('Auctioneers Outstanding', 'Assets','Auct_out'),
                    array('Fixed Assets', 'Assets','Fixed_Assets'),
                    array('Microfinance Loan', 'Assets','Micro_loan'),
                    array('Union Bank Loan', 'Assets','Union_Bank_Loan'),
                    array('Costal Bank Loan', 'Assets','Costal_Bank_Loan'),
                    array('Indian Overseas Bank Loan', 'Assets','IOB_Loan'),
                    array('Kerosine Repayable', 'Assets','Kerosine_Repayable'),
                    array('Current Assets', 'Assets','Current_Assets')
                   
                );
        return $heads;
    }
    
    public function commonMFBSHeads()
    {
        $heads = array(
                    array('FWS A/c', 'Liability','FWS_Account'),
                    array('Union Bank Loan', 'Liability','Union_Bank_Loan_Liable'),
                    array('Advance Receieved', 'Liability','MF_Advance_Receieved'),           
                    array('FMC Grant', 'Liability','FMC_Grant'),            
                    array('Bad debts to be written off', 'Liability','MF_Bad_Debs_Written_off'),                    
                    array('Costal Bank Loan', 'Liability','Costal_Bank_Loan_Liable'),         
                    array('FWS New Loan', 'Liability','FWS_New_Loan_Liable'),
                    array('Indian Overseas Bank Loan', 'Liability','IOB_Loan_Liable'),
                    array('Costal Bank Bulk Loan', 'Liability','Costal_Bank_Bulk_Loan_Liable'),    
                    array('FWS Grant', 'Liability','FWS_Grant'),                  
                    array('MF Loan To FMCs', 'Assets','MFLoan_To_FMCs'),
                    array('Union Bank Loan', 'Assets','Union_Bank_Loan'),
                    array('Auctioneers Loan', 'Assets','Auctioneers_Loan'),
                    array('Costal Bank Loan', 'Assets','Costal_Bank_Loan'),
                    array('FWS New Loan', 'Assets','FWS_New_Loan'),
                    array('Indian Overseas Bank Loan', 'Assets','IOB_Loan'),            
                    array('Advance Paid', 'Assets','MF_Advance'),            
                    array('Staff Loan', 'Assets','Staff_Loan'),
                    array('Fixed Assets', 'Assets','Fixed_Assets'),
                    array('Current Assets', 'Assets','Current_Assets')
                   
                   
                );
        return $heads;
    }
    
     public function commonFWSBSHeads()
    {
        $heads = array(
                    array('Siffs Grant (Old)', 'Liability','Siffs_Grant_Old'),
                    array('SIFFS Grant (New)', 'Liability','SIFFS_Grant_New'),
                    array('Staff Welfare Funds', 'Liability','Staff_Welfare_Funds'),           
                    array('Sundry Payables', 'Liability','Sundry_Payables'),            
                    array('Provision for Bad debts', 'Liability','Provision_for_Bad_debts'),                    
                    array('Loans/Advances Taken OS', 'Liability','Loans_Advances_Taken_OS'),     
                    array('Costal Bank Bulk Loan', 'Liability','Costal_Bank_Bulk_Loan_Liable'), 
                    array('Educational Scholarship Fund ', 'Liability','Educational_Scholarship_Fund'),
                    array('Gratuity Fund', 'Liability','Gratuity_Fund'),
                    array('DMF Grant', 'Liability','DMF_Grant'),
                    array('Loans OS with Branches (old)', 'Assets','Loans_OS'),
                    array('Land and Buildings', 'Assets','Land_Buildings'),
                    array('Furniture/Fittings/Equipments', 'Assets','Furniture_Fittings'),
                    array('Staff PF Advance Paid', 'Assets','Staff_PF'),
                    array('Loans/Advances Given OS', 'Assets','Loans_Advances_OS'),
                    array('Chitty Collections Receivable', 'Assets','Chitty_Collection'),            
                    array('Loan OS with MF (Credit)', 'Assets','Loans_OS_MF'),            
                    array('Working Capital Loan OS with CKMU', 'Assets','Working_Capital_Loan'),
                    array('Costal Bank Bulk Loan', 'Assets','Costal_Bulk_Bank_Loan'),
                    array('Educational Scholarship Fund Deposit', 'Assets','Educational_Scholarship'),
                    array('Gratuity Fund Recievable From Staff', 'Assets','Gratuity_Fund'),
                    array('DMF Grant Pay Kit', 'Assets','DMF_Grant'),
                    array('Current Assets', 'Assets','Current_Assets')
                );
        return $heads;
    }
     public function MFHeads()
    {
        $heads = array(
                    array('Union Bank Loan', 'Direct Expense','UBL'),
                    array('Union Bank Loan', 'Direct Income','UBLI'),
                    array('Union Bank of India', 'Bank','UBI'),
                    array('IOB', 'Bank','IOB'),
                    array('Coastal Bank', 'Bank','coast_bank'),
                    array('Coastal Bank Loan', 'Direct Expense','CBL'),
                    array('Coastal Bank Loan', 'Direct Income','CBLI'),
                    array('IOB Loan', 'Direct Expense','IOB'),
                    array('IOB Loan', 'Direct Income','IOBI'),
                    array('FWS New Loan', 'Direct Expense','FWSL'),
                    array('FWS New Loan', 'Direct Income','FWSLI'),
                    array('MF Loan', 'Direct Expense','MFL'),
                    array('MF Loan', 'Direct Income','MFLI'),
                    array('Bank Loan Collection withdrawal', 'Direct Expense','BLC'),
                    array('Cash', 'Cash','cash'),
                    array('Bulk Loans', 'Direct Income','bulk_loans'),                    
            
                );
        return $heads;
    }
    
     /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $id = $this->getParamId();
        $valid = false;
        if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                if (! $this->_headModel->checkByColumnId('fmc_id', $id)) {
                    $valid = true;
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'head');
        }
        $this->view->setVariable('fmcId', $id);
        $model = new RPHeads();
        $fmc_name = $this->_fmcModel->selectColumnById('name', $id);
        if($fmc_name === 'MF'){
            $MFHeads = $this->MFHeads();
            foreach ($MFHeads as $head){
                $insert['head_name'] = $head[0];
                $insert['head_alias'] = $head[0];
                $insert['code'] = $head[2];
                $insert['transType'] = $head[1];
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
            }
        } else {        
            $list = $this->_deductionTypeModel->select(null, 'priority');
            $insert = [];
            foreach ($list as $item) {
                $insert['head_name'] = $item->deduction_type;
                $insert['head_alias'] = $item->malayalam;
                $insert['code'] = $item->code;
                $insert['transType'] = 'Direct Income';
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
            }

            $insert = [];
            foreach ($list as $item) {
                $insert['head_name'] = $item->deduction_type;
                $insert['head_alias'] = $item->malayalam;
                $insert['code'] = 'exp_'.$item->code;
                $insert['transType'] = 'Direct Expense';
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
            }
            $insert['head_name'] = 'Cash';
            $insert['head_alias'] = 'Cash';
            $insert['code'] = 'cash';
            $insert['transType'] = 'Cash';
            $insert['opb'] = 0;
            $insert['fmc_id'] = $id;
            $insert['status'] = 'N';
            $model->insert($insert);  

            $insert['head_name'] = 'Costal Urban Bank';
            $insert['head_alias'] = 'Costal Urban Bank';
            $insert['code'] = 'CUB';
            $insert['transType'] = 'Bank';
            $insert['opb'] = 0;
            $insert['fmc_id'] = $id;
            $insert['status'] = 'N';
            $model->insert($insert); 
            $donorQuery = "select code, name from  " . $this->_memberModel->getTableQuoted()." where member_type_id =2 and fmc_id = {$id} and disabled = 0 order by name";
            $donors = $this->_memberModel->queryRows($donorQuery);
            foreach($donors as $donor){
                $name = explode(" ", $donor->name);
                //Auction collection
                $headName = "{$donor->code}-".$name[0]."-Auct Collection";
                $insert['head_name'] = $headName;
                $insert['head_alias'] = $headName;
                $insert['code'] = $donor->code;
                $insert['transType'] = 'Direct Income';
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
                 //Auction Commission
                $headName = "{$donor->code}-".$name[0]."-Auct Commission";
                $insert['head_name'] = $headName;
                $insert['head_alias'] = $headName;
                $insert['code'] = "Comm_".$donor->code;
                $insert['transType'] = 'Direct Expense';
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
            }
            $otherHeads = $this->commonHeads();
            foreach ($otherHeads as $head){
                $insert['head_name'] = $head[0];
                $insert['head_alias'] = $head[0];
                $insert['code'] = $head[2];
                $insert['transType'] = $head[1];
                $insert['opb'] = 0;
                $insert['fmc_id'] = $id;
                $insert['status'] = 'N';
                $model->insert($insert);
            }
        }
        $this->_loadFmc($id);
        $this->_loadFormOptions();
        $this->_breadcrumb('Configure');
        
        return $this->getViewModel();
    }
    
        
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->getParamId();
        $valid = false;
        if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                $valid = $this->_headModel->checkByColumnId('fmc_id', $id);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'accounts');
        }
        $distintIDs = [];
        $sql = $this->_headModel->getSql();
        $sql->whereId('fmc_id', $id);
        $sql->whereEqual('status', 'N');
        $list = $this->_headModel->select($sql->getWhere(), 'id');
        
        $RPTransModel = new RPTransactionDetails();
        $sql = $RPTransModel->getSql();
        $sql->whereId('fmc_id', $id);
        $rpTransUsed = $RPTransModel->selectDistinct('headID', $sql->getWhere(), 'headID');
        //$rpTransUsed = $RPTransModel->selectDistinct('headID');
        //$this->view->setVariable('used', $boatModel->selectDistinct('headID'));
        $this->_membTrans = new RPTransMember();
        $this->_trnsDetmodel = new RPTransMemberDetails();
        $sql = "SELECT DISTINCT headID FROM ". $this->_trnsDetmodel->getTableQuoted() . " `t` "
                    . "inner join " . $this->_membTrans->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "  where 1=1 and `m`.`fmc_id` = {$id}";
        $data = $this->_trnsDetmodel->queryRows($sql);   
        foreach ($data as $item){
            $distintIDs[] = $item->headID;
        }
        $used = array_merge($rpTransUsed, $distintIDs);
        $this->view->setVariable('used', $used);
        
        $this->view->setVariable('list', $list);
        $this->view->setVariable('fmcId', $id);
        $this->_loadFmc($id);
        $this->addModuleScript('accounts/head-type');
        $this->layout->addModal('accounts/head-type');
        $this->_breadcrumb('View');

        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function bscreateAction()
    {
        $id = $this->getParamId();
        $valid = false;
        if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                if (! $this->_bsheadModel->checkByColumnId('fmc_id', $id)) {
                    $valid = true;
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'head');
        }
        $this->view->setVariable('fmcId', $id);
        $model = new RPBSHeads();
        if($id ==  FormHelper::MF_ACCOUNT_ID){
            $otherHeads = $this->commonMFBSHeads();
        } else if($id ==  FormHelper::FWS_ACCOUNT_ID){
            $otherHeads = $this->commonFWSBSHeads();
        } else {
            $otherHeads = $this->commonBSHeads();
        }
        foreach ($otherHeads as $head){
            $insert['head_name'] = $head[0];
            $insert['head_alias'] = $head[0];
            $insert['code'] = $head[2];
            $insert['transType'] = $head[1];
            $insert['opb'] = 0;
            $insert['fmc_id'] = $id;
            $insert['status'] = 'N';
            $insert['fy'] =  '2025-26';
            $model->insert($insert);
        }
        $this->_loadFmc($id);
        $this->_loadFormOptions();
        $this->_breadcrumb('Configure');
        
        return $this->getViewModel();
    }
    
      /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function bsresetviewAction()
    {
        $fmc_id = $this->getParamId();
        $this->_fmc_id = $fmc_id;
        $fy = $this->getParam('fy', false);
        list($startYear, $endYearShort) = explode('-', $fy);
        // Next FY
        $nextStart = $startYear + 1;
        $nextEnd   = ($nextStart + 1) % 100; // last 2 digits
        $nextFy = $nextStart . '-' . str_pad($nextEnd, 2, '0', STR_PAD_LEFT);
        $valid = false;
      /*  if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                $valid = $this->_bsheadModel->checkByColumnId('fmc_id', $id);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'accounts');
        }*/
        $fyQuery = "SELECT fmc_id, MAX(fy) AS latest_fy from" . $this->_bsheadModel->getTableQuoted()." where fmc_id={$fmc_id}";
        $latest_fy = $this->_bsheadModel->queryRows($fyQuery);
        $currentFY = $latest_fy[0]->latest_fy;
        if($currentFY !== $nextFy) {
             $this->resetBSOpeningBalance($fmc_id, $fy);
        }
        $distintIDs = [];

        $sql = $this->_bsheadModel->getSql();
        $sql->whereId('fmc_id', $fmc_id);
        $sql->whereEqual('fy', $nextFy);
        $list = $this->_bsheadModel->select($sql->getWhere(), 'id');
        $this->view->setVariable('list', $list);
        $this->view->setVariable('fmcId', $fmc_id);
        $this->_loadFmc($fmc_id);
        $this->addModuleScript('accounts/bshead-type');
        $this->layout->addModal('accounts/bshead-type');
        $this->_breadcrumb('View');

        return $this->getViewModel();
    }
    
    private function setNewBSOpeningBalance($data)
    {
        $model = new RPBSHeads();
            $insert['head_name'] = $data['head_name'];
            $insert['head_alias'] = $data['head_alias'];
            $insert['code'] = $data['code'];
            $insert['transType'] = $data['transType'];
            $insert['opb'] =  $data['opb'];
            $insert['fmc_id'] =  $data['fmc_id'];
            $insert['status'] =  $data['status'];
            $insert['fy'] = $data['fy'];
            $model->insert($insert);
    }
     /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
     private function resetBSOpeningBalance($fmc_id, $fy)
    {
        $catchOpening = 0;
        $catchOpeningTotal = 0;
        $totalMemberPayments = 0;
        $memberAdvanceSum =0;
        $donorOutStandingOpening =0;
        $donorCollectionPayment = 0;
        $donorCollectionPaymentTotal= 0;
        $totalAssetsBalance = 0;
        $totalFMCLoanAmount = 0;
        $fmcLoanOpening = 0;
        $donorAssets =0;
        $fmcLoanReceipt = 0;
        $fmcLoanReceiptTotal = 0;
        $fmcAdvReceipt = 0;
        $fmcAdvOpening = 0;
        $fmcAdvTotals = 0;
        $fmcOpening =0;
        $fmcAssetsTotals =0;
        $fmcReceiptTotal =0;
        $liquidated = 0;
        $currentYearLiability = 0;
        $refundedLiability = 0;
        $currentYearTotalLiability = 0;
        $cashOpening = 0;
        $bankOpening = [];
        $subTotalBankOpening = 0;
        $cashClosing = 0;
        $subTotalBankClosing =0;
        $assetsOpeningTotal = 0;
        $assetsBalanceTotal = 0;
        $liabilityOpeningTotal = 0;
        $liabilityBalanceTotal = 0;
        $operatingLossCol1 = 0;

        ///
        $bankOpening = [];
        $bankClosing = [];
        $assetsItems = [];
        $liablilityItems = [];
        $this->_report['opb'] = 0;
        $this->_report['report'] = [];
        $bankTransTotal = [];
        $cashTotal = 0;
        $reportTotal = 0;
        $cashTransTotal = [];
        $cashPaymentTotal = 0;
        $receiptBankTotal = 0;
        $cashClosing = 0;
        $byBankTotal = 0;
        $bycashTotal = 0;
        $bankTotal = 0;
        $toBankPaymentTotal = 0;
        $getMemberReceipts = [];
        $paymentTotal = 0;
        $cashReciptTotal = 0;
        $fmcAdvReceiptTotal = 0;
        $x = 0;
        $matchingHeadId = 0;
        $catchTotal =0;
        $memberAdvancePaid = 0;
        $month = date("F", strtotime($this->_endDate));
        $year = date('Y', strtotime($this->_endDate));
             
        $startYear = explode('-', $fy)[0];
        $CFinancialYearWD = $startYear . "-04-01";
        $endYear = explode('-', $fy)[1];
        $CEFinancialYearWD = $endYear . "-03-31";
        $this->_endDate = $CEFinancialYearWD;
        $this->_startDate = $CFinancialYearWD;
        $sdate = new DateTime($this->_startDate);
        $stimestamp = $sdate->getTimestamp();
        $edate = new DateTime($this->_endDate);
        $etimestamp = $edate->getTimestamp();
        list($startYear, $endYearShort) = explode('-', $fy);
        // Next FY
        $nextStart = $startYear + 1;
        $nextEnd   = ($nextStart + 1) % 100; // last 2 digits
        $nextFy = $nextStart . '-' . str_pad($nextEnd, 2, '0', STR_PAD_LEFT);
        $headsQuery = "select * from " . $this->_bsheadModel->getTableQuoted()." where TransType in ('Assets','Liability') and status='N' and fmc_id = {$fmc_id} and fy='{$fy}' order by id";
        $BSHeads = $this->_bsheadModel->queryRows($headsQuery);
        $bankQuery = "select * from  " . $this->_headModel->getTableQuoted()." where TransType ='bank' and fmc_id = {$fmc_id} order by head_name";
        $bankHeads = $this->_headModel->queryRows($bankQuery);     
        $liabilityCount = 0;
        $assetsCount = 0;
        $cashOpening = $this->getCashOpening($CFinancialYearWD, $fmc_id);
        $cashClosing = $this->getCashTransactions($cashOpening, $fmc_id);
        foreach ($bankHeads as $rows) {
            $bank_opening = $this->getBankOpening($rows->id, $CFinancialYearWD, $fmc_id);
            if(isset($bank_opening)) {
                $bankOpening[$rows->id]['head_name'] = $rows->head_name;
                $bankOpening[$rows->id]['amount'] =  $bank_opening;
                $subTotalBankOpening += $bank_opening;
            }
        }
        //Bank transactions for closing
        foreach ($bankHeads as $rows) {
            $bank_closing = $this->getBankTransactions($rows->id, $bankOpening[$rows->id]['amount'], $fmc_id);
            if(isset($bank_closing)) {
                $bankClosing[$rows->id]['head_name'] = $rows->head_name;
                $bankClosing[$rows->id]['amount'] =  $bank_closing;
                $subTotalBankClosing += $bank_closing;
            }
        }
        foreach($BSHeads as $bs_item){
            $headSum = 0;
            $y = 0;
            $monthTotal = 0;
            $cumulativeTotal = 0;
             if($bs_item->code === 'FWS_loan_repayment'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='FWS_loan_repayment' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='FWS_loan_repayment' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }
             
             if($bs_item->code === 'Advance_Receieved'){
                                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='FWS_Advance' and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='inc_FMC_Advance' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }
             if($bs_item->code === 'External_Debt'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='outside_loan' and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Inc_outside_loan' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }   
             if($bs_item->code === 'Deposits'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('exp_ret_fund', 'exp_gift_fund','exp_labour_savings','exp_common_savings','exp_fmc_member', 'exp_eng_savings', 'E/S', 'exp_fixed_savings', 'exp_ret_fund', 'exp_self_savings','exp_special_savings', 'exp_kerosene', 'exp_subs_fund', 'exp_fund', 'E/S', 'exp_xmas_fund') and transType='Direct Expense' and  fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('ret_fund', 'gift_fund','labour_savings','common_savings', 'eng_savings', 'E/S', 'fmc_member', 'ret_fund','fixed_savings', 'self_savings', 'subs_fund', 'subs_fund', 'fund', 'kerosene', 'special_savings', 'xmas_fund') and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }   
              if($bs_item->code === 'Security_Deposits'){
                                  $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='exp_security_deposits' and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='security_deposits' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }   
             if($bs_item->code === 'Amount_Payable'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('exp_branch_union', 'exp_auction', 'exp_union', 'exp_death_fund', 'exp_fws') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('auction', 'union', 'death_fund', 'fws', 'branch_union') and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }   
             if($bs_item->code === 'Micro_loan_Liable'){
                $refundedLiability = 0;
                $accrualInterest = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $loanQuery = "select * from " . $this->_loanSource->getTableQuoted()." where code ='MFL'";
                $loanID = $this->_loanSource->queryRows($loanQuery);
                $interestSQL = "SELECT SUM(collection_current_interest) AS total_current_interest, SUM(collection_interest_arrears) AS total_interest_arrears FROM " . $this->_scheduleModel->getTableQuoted()." INNER JOIN loan ON loan.id=schedule.loan_id INNER JOIN member ON member.id=loan.member_id WHERE loan.loan_source_id={$loanID[0]->id} AND member.fmc_id= {$fmc_id} AND  schedule.dcb_created_at between {$stimestamp} and {$etimestamp} ";
                $fmcInterestTotal = $this->_scheduleModel->queryRows($interestSQL);
                $accrualInterest  = $fmcInterestTotal[0]->total_current_interest +  $fmcInterestTotal[0]->total_interest_arrears;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('IOB_MF_loan_repaid', 'UBL_MF_loan_pre_closure', 'UBL_MF_loan_repaid', 'MFL_loan_repaid', 'MFL_loan_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }
                  if ($accrualInterest > 0 && $refundedLiability > 0) {
                    $refundedLiability -= $accrualInterest;

                    if ($refundedLiability < 0) {
                        $refundedLiability = 0;
                    }
                }
                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             }   
             
             if($bs_item->code === 'Union_Bank_Loan_Liable'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                //Get Interest
                $loanQuery = "select * from " . $this->_loanSource->getTableQuoted()." where code ='UBL'";
                $loanID = $this->_loanSource->queryRows($loanQuery);
                $interestSQL = "SELECT SUM(collection_current_interest) AS total_current_interest, SUM(collection_interest_arrears) AS total_interest_arrears FROM " . $this->_scheduleModel->getTableQuoted()." INNER JOIN loan ON loan.id=schedule.loan_id INNER JOIN member ON member.id=loan.member_id WHERE loan.loan_source_id={$loanID[0]->id} AND member.fmc_id= {$fmc_id} AND  schedule.dcb_created_at between {$stimestamp} and {$etimestamp} ";
                $fmcInterestTotal = $this->_scheduleModel->queryRows($interestSQL);
                $accrualInterest  = $fmcInterestTotal[0]->total_current_interest +  $fmcInterestTotal[0]->total_interest_arrears;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('UBL_loan_repaid','UBL_loan_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='UBI_loan' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }
                if ($accrualInterest > 0 && $refundedLiability > 0) {
                    $refundedLiability -= $accrualInterest;

                    if ($refundedLiability < 0) {
                        $refundedLiability = 0;
                    }
                }
                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
             
              if($bs_item->code === 'Costal_Bank_Loan_Liable'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $accrualInterest = 0;
                $currentYearTotalLiability = 0;
                $loanQuery = "select * from " . $this->_loanSource->getTableQuoted()." where code ='CBL'";
                $loanID = $this->_loanSource->queryRows($loanQuery);
                $interestSQL = "SELECT SUM(collection_current_interest) AS total_current_interest, SUM(collection_interest_arrears) AS total_interest_arrears FROM " . $this->_scheduleModel->getTableQuoted()." INNER JOIN loan ON loan.id=schedule.loan_id INNER JOIN member ON member.id=loan.member_id WHERE loan.loan_source_id={$loanID[0]->id} AND member.fmc_id= {$fmc_id} AND  schedule.dcb_created_at between {$stimestamp} and {$etimestamp} ";
                $fmcInterestTotal = $this->_scheduleModel->queryRows($interestSQL);
                $accrualInterest  = $fmcInterestTotal[0]->total_current_interest +  $fmcInterestTotal[0]->total_interest_arrears;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('UBL_loan_repaid','UBL_loan_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where  code in('CBL_loan_pre_closure', 'CBL_loan_repaid') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('Coastal_Bank_Loan', 'CBL_loan') and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }
               
                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
               if ($accrualInterest > 0 ) {
                    $refundedLiability -= $accrualInterest;
                    if ($refundedLiability < 0) {
                        $refundedLiability = 0;
                    }
                }
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
             
             if($bs_item->code === 'IOB_Loan_Liable'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $loanQuery = "select * from " . $this->_loanSource->getTableQuoted()." where code ='UBL'";
                $loanID = $this->_loanSource->queryRows($loanQuery);
                $interestSQL = "SELECT SUM(collection_current_interest) AS total_current_interest, SUM(collection_interest_arrears) AS total_interest_arrears FROM " . $this->_scheduleModel->getTableQuoted()." INNER JOIN loan ON loan.id=schedule.loan_id INNER JOIN member ON member.id=loan.member_id WHERE loan.loan_source_id={$loanID[0]->id} AND member.fmc_id= {$fmc_id} AND  schedule.dcb_created_at between {$stimestamp} and {$etimestamp} ";
                $fmcInterestTotal = $this->_scheduleModel->queryRows($interestSQL);
                $accrualInterest  = $fmcInterestTotal[0]->total_current_interest +  $fmcInterestTotal[0]->total_interest_arrears;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('UBL_loan_repaid','UBL_loan_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('IOB_loan_repaid','IOB_loan_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='IOB_loan' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
               if ($accrualInterest > 0 && $refundedLiability > 0) {
                    $refundedLiability -= $accrualInterest;

                    if ($refundedLiability < 0) {
                        $refundedLiability = 0;
                    }
                }
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
             
             if($bs_item->code === 'Bad_Debs_Written_off'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Advance_Receieved' and transType='Direct Expense'  and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Advance_Receieved' and transType='Direct Income'  and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
             
              if($bs_item->code === 'Bank_Loan_Collection'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('IOB_loan_repaid','IOB_loan_pre_closure', 'exp_bank_loan', 'CBL_loan_repaid','CBL_loan_pre_closure', 'MFL_loan_repaid', 'MFL_loan_pre_closure', 'UBL_loan_repaid','UBL_MF_loan_pre_closure','UBL_loan_pre_closure', 'IOB_MF_loan_repaid', 'UBL_MF_loan_repaid') and transType='Direct Expense'  and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='bank_loan' and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                 $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
             
             if($bs_item->code === 'Insurance'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='exp_insurance' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='insurance' and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
          
             if($bs_item->code === 'FMC_Bulk_Loan'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='exp_bulk_loans' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='bulk_loans' and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $currentYearTotalLiability - $refundedLiability; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
             } 
            //FMC Loan Repayable
           //get FMC loan payment transactions
            if($bs_item->code === 'FMC_Loan_rep'){
                $loanHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in ('exp_fmc_loan','exp_relief_loan','personal', 'relief', 'production', 'exp_thattumadi', 'exp_labour_loan','relief2') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcLoanHeads = $this->_headModel->queryRows($loanHeadsQuery);
                foreach($fmcLoanHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $totalFMCLoanAmount += $headSum; 
                   //  $fmcLoanReceipt = $this->getSumByHeadID($item->id);  
                   //  $fmcLoanReceiptTotal += $fmcLoanReceipt;
                }
                //get FMC loan receipt transactions
                $loanRHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in ('fmc_loan', 'production_loan', 'thattumadi', 'labour_loan', 'relief_loan', 'DCB_re_finance', 'DCB_pre_closure') and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $fmcLoanRHeads = $this->_headModel->queryRows($loanRHeadsQuery);
                foreach($fmcLoanRHeads as $item){
                     $fmcLoanReceipt = $this->getSumByHeadID($item->id);  
                     $fmcLoanReceiptTotal += $fmcLoanReceipt;
                }

                $y = 0;
                $fmcLoanOpening = $bs_item->opb;
                $fmcLoanTotals = $totalFMCLoanAmount + $fmcLoanOpening;               

                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $fmcLoanTotals - $fmcLoanReceiptTotal; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
               
            }
          if($bs_item->code === 'FMC_Advance'){
                //Advance paid 
                $fmcAdvancePaid = 0;
                $loanHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in ('exp_FMC_Advance') and fmc_id = {$fmc_id} and transType='Direct Expense' order by head_name";
                $fmcLoanHeads = $this->_headModel->queryRows($loanHeadsQuery);
                foreach($fmcLoanHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $fmcAdvancePaid += $headSum; 
                   //  $fmcLoanReceipt = $this->getSumByHeadID($item->id);  
                   //  $fmcLoanReceiptTotal += $fmcLoanReceipt;
                }
                //get FMC advance receipt transactions
                $loanFHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='inc_FMC_Advance_recovered' and fmc_id = {$fmc_id} and transType='Direct Income' order by head_name";
                $fmcLoanFHeads = $this->_headModel->queryRows($loanFHeadsQuery);
                foreach($fmcLoanFHeads as $item){
                     $fmcAdvReceipt = $this->getSumByHeadID($item->id);  
                     $fmcAdvReceiptTotal += $fmcAdvReceipt;
                }
                $fmcAdvOpening = $bs_item->opb;
                $y = 0;
                $fmcAdvTotals = $fmcAdvancePaid + $fmcAdvOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] = $fmcAdvTotals - $fmcAdvReceiptTotal;  
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
         }
   
          if($bs_item->code === 'Auct_out'){
               //Get donors opening amount. 
                    //Check donor ledger
                    $donorOutStandingOpening = $bs_item->opb;
                    $donorQuery = "select * from " . $this->_memberModel->getTableQuoted()." where disabled=0 and fmc_id = {$fmc_id} and member_type_id = 2";
                    $donors = $this->_memberModel->queryRows($donorQuery);
                    foreach($donors as $donor){
                        $auctHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where code='{$donor->code}' and fmc_id = {$fmc_id} order by head_name";
                        $heads = $this->_headModel->queryRows($auctHeadQuery);
                        $openingChits = $this->auctionChitIdsOpening($donor->id, $fmc_id);
                        if(is_array($openingChits)){
                            $catchOpening = $this->periodSumOpening($openingChits); //Catch Total   
                        }
                        $donorCollectionPayment = $this->getSumByHeadID($heads[0]->id);
                        $donorCollectionPaymentTotal += $donorCollectionPayment;
                    } 
                    $MemberAdvanceHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='Member Advance' and code='memb_advance' and fmc_id = {$fmc_id} order by head_name";
                    $advanceHeads = $this->_headModel->queryRows($MemberAdvanceHeadQuery);
                    $memberAdvanceHeadID = $advanceHeads[0]->id;
                    $memberAdvanceSum = $this->getPaymentyHeadID($memberAdvanceHeadID);
                    $MemberPaymentHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='Member Payment' and code='memb_payment' and fmc_id = {$fmc_id} order by head_name";
                    $memberPaymentHeads = $this->_headModel->queryRows($MemberPaymentHeadQuery);
                    $memberPaymentHeadID = $memberPaymentHeads[0]->id;
                    $memberPaymentSum = $this->getPaymentyHeadID($memberPaymentHeadID);
                    $totalMemberPayments = $memberAdvanceSum + $memberPaymentSum;
                    //$donorOutStandingOpening = $catchOpeningTotal - $totalMemberPayments;
                    $donorAssets = ($totalMemberPayments + $donorOutStandingOpening)  - $donorCollectionPaymentTotal;
                    $totalAssetsBalance = $totalMemberPayments + $donorOutStandingOpening;
                    $y = 0;                
                    $data = [];
                    $data['head_name'] = $bs_item->head_name;
                    $data['head_alias'] = $bs_item->head_name;
                    $data['code'] = $bs_item->code;
                    $data['transType'] = $bs_item->transType;
                    $data['opb'] = $donorAssets; 
                    $data['fmc_id'] = $fmc_id;
                    $data['status'] = 'N';
                    $data['fy'] = $nextFy;
                    $this->setNewBSOpeningBalance($data);
            }
            if($bs_item->code === 'Fixed_Assets'){
                $fmcReceiptTotal = 0 ;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='liquidated_assets' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('fixed_assets','furn_other_asset') and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
            
            if($bs_item->code === 'Micro_loan'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('IOB_MF_loan_repaid', 'UBL_MF_loan_pre_closure', 'UBL_MF_loan_repaid', 'MFL_loan_repaid', 'MFL_loan_pre_closure') and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
            
            if($bs_item->code === 'Union_Bank_Loan'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('UBL_loan_repaid','UBL_loan_pre_closure') and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='UBI_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
            
            if($bs_item->code === 'Costal_Bank_Loan'){
                 $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('CBL_loan_pre_closure', 'CBL_loan_repaid') and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='CBL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);

            }
            
            if($bs_item->code === 'IOB_Loan'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('IOB_loan_repaid','IOB_loan_pre_closure') and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='IOB_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
                 $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
             if($bs_item->code === 'Kerosine_Repayable'){
                $fmcReceiptTotal = 0 ;
                $liquidated= 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='exp_kerosene__' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='kerosene___' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $fmcAssetsTotals - $liquidated; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
             if($bs_item->code === 'Current_Assets'){
                  $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='liquidated_assets' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_headModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $data = [];
                $data['head_name'] = $bs_item->head_name;
                $data['head_alias'] = $bs_item->head_name;
                $data['code'] = $bs_item->code;
                $data['transType'] = $bs_item->transType;
                $data['opb'] =  $cashClosing + $subTotalBankClosing; 
                $data['fmc_id'] = $fmc_id;
                $data['status'] = 'N';
                $data['fy'] = $nextFy;
                $this->setNewBSOpeningBalance($data);
            }
        }
         return true;
    }
     private function getCashOpening($date, $fmc_id)
    {
            $cashTotal = 0;
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
    
     private function getCashTransactions($opb, $fmc_id)
    {
            $cashTotal = 0;
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
    
    private function getBankOpening($headID , $date, $fmc_id)
    {
            $opb = 0;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`id` = '{$headID}' and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            if(isset($data[0]->opb)){
               $opb = $data[0]->opb;
            }
        //Cash Deposit to Bank
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '{$date}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $deposit =  $data[0]->sum;
        //Bank Receipts start here (Interest, tds refund etc
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` < '{$date}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $receiptSum = $data[0]->sum;
            //Member Receipts
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` < '{$date}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            $memberReceipt =  $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb + $deposit;
        //Bank Receipts ends here
        //Payments through Bank start here
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` < '{$date}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $bankPayment =  $data[0]->sum;
        //Bank payment ends here
        //Bank Withdrawl start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '{$date}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $withdrawl = $data[0]->sum;
        //Bank payment ends here 
            $payments = $bankPayment + $withdrawl;

            return round(($cashTotal - $payments),2);
    }
    
    private function getBankTransactions($headID, $opb, $fmc_id)
    {
        //Cash Deposit to Bank
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $deposit =  $data[0]->sum;
        //Bank Receipts start here (Interest, tds refund etc
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $receiptSum = $data[0]->sum;
            //Member Receipts
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            $memberReceipt =  $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb + $deposit;
        //Bank Receipts ends here
        //Payments through Bank start here
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $bankPayment =  $data[0]->sum;
        //Bank payment ends here
        //Bank Withdrawl start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $withdrawl = $data[0]->sum;
        //Bank payment ends here 
            $payments = $bankPayment + $withdrawl;

            return round(($cashTotal - $payments),2);
    }
       private function getPaymentyHeadID($headID)
    {
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`headID` ={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
    }
     private function getSumByHeadID($headID)
   {
        $headSum = 0;
         $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` "
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                . "where 1=1 and `t`.`headID` ={$headID} and `m`.`fmc_id` = {$fmc_id} and `m`.`status` = '1' "
                . "and `m`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}'";
        $data = $this->_membTrans->queryRows($sql);
        if(isset($data[0]->sum)){
            $headSum += $data[0]->sum;
        }
       // if(!$data[0]->sum) {
             $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                     . " where 1=1 and `t`.headID='{$headID}' and `t`.`transType` ='R' and `t`.`transBy` >0 and `t`.`status` = '1' "
                     . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data_next = $this->_transactionModel->queryRows($sql);
            if(isset($data_next[0]->sum)){
                $headSum += $data_next[0]->sum;
            }
       // }
        return ($headSum);
    }
       
    public function periodSumOpening($auctionChitID)
    {
        $sql = $this->_transactionModel->getSql();
        $sql->whereIn('action_chit_id', $auctionChitID);
        $where = $sql->getWhere();
        if (0 == $this->_transactionModel->selectTotal($where)) {
            return 0;
        }
        
        return $this->_transactionModel->selectSum('amount', $where);
    }
    public function bsviewAction()
    {
        $id = $this->getParamId();
        $fyQuery = "SELECT fmc_id, MAX(fy) AS latest_fy from" . $this->_bsheadModel->getTableQuoted()." where fmc_id={$id}";
        $fy = $this->_bsheadModel->queryRows($fyQuery);
        $currentFY = $fy[0]->latest_fy;
        $valid = false;
        if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                $valid = $this->_bsheadModel->checkByColumnId('fmc_id', $id);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'accounts');
        }
        $distintIDs = [];
        $sql = $this->_bsheadModel->getSql();
        $sql->whereId('fmc_id', $id);
        $sql->whereEqual('fy', $currentFY);
        $list = $this->_bsheadModel->select($sql->getWhere(), 'id');
        $this->view->setVariable('list', $list);
        $this->view->setVariable('fmcId', $id);
        $this->_loadFmc($id);
        $this->addModuleScript('accounts/bshead-type');
        $this->layout->addModal('accounts/bshead-type');
        $this->_breadcrumb('View');

        return $this->getViewModel();
    }
    
      /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function bsviewActionOld()
    {

        $distintIDs = [];
        $sql = $this->_bsheadModel->getSql();
       // $sql->whereId('fmc_id', $id);
        $sql->whereEqual('status', 'N');
        $list = $this->_bsheadModel->select($sql->getWhere(), 'id');
        
       /* $RPTransModel = new RPTransactionDetails();
        $sql = $RPTransModel->getSql();
        $sql->whereId('fmc_id', $id);
        $rpTransUsed = $RPTransModel->selectDistinct('headID', $sql->getWhere(), 'headID');
        //$rpTransUsed = $RPTransModel->selectDistinct('headID');
        //$this->view->setVariable('used', $boatModel->selectDistinct('headID'));
        $this->_membTrans = new RPTransMember();
        $this->_trnsDetmodel = new RPTransMemberDetails();
        $sql = "SELECT DISTINCT headID FROM ". $this->_trnsDetmodel->getTableQuoted() . " `t` "
                    . "inner join " . $this->_membTrans->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "  where 1=1 and `m`.`fmc_id` = {$id}";
        $data = $this->_trnsDetmodel->queryRows($sql);   
        foreach ($data as $item){
            $distintIDs[] = $item->headID;
        }
        $used = array_merge($rpTransUsed, $distintIDs);
        $this->view->setVariable('used', $used);*/
        
        $this->view->setVariable('list', $list);
        //$this->view->setVariable('fmcId', $id);
       // $this->_loadFmc($id);
        $this->addModuleScript('accounts/bshead-type');
        $this->layout->addModal('accounts/bshead-type');
        $this->_breadcrumb('View');

        return $this->getViewModel();
    }
    
     /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function configAction()
    {
        $id = $this->getParamId();
        $valid = false;
        if ($id > 0) {
            if ($this->_fmcModel->hasId($id)) {
                $valid = true;
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'accounts');
        }
        
        $sql = $this->_accountSettingModel->getSql();
        $sql->whereId('fmc_id', $id);
        $list = $this->_accountSettingModel->select($sql->getWhere(), 'id');
       
        $this->view->setVariable('list', $list);
        $this->view->setVariable('fmcId', $id);
        $this->_loadFmc($id);
        $this->addModuleScript('accounts/settings-type');
        $this->layout->addModal('accounts/settings-type');
        $this->_breadcrumb('View');

        return $this->getViewModel();
    }
    /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function headTypeAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageHeads($this->getAjaxResponse());
        switch ($this->getParam('type')) {
            case 'head':
                $result = $manage->head();
                break;
            default:
                break;
        }
        
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function bsheadTypeAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageBSHeads($this->getAjaxResponse());
        switch ($this->getParam('type')) {
            case 'head':
                $result = $manage->head();
                break;
            default:
                break;
        }
        
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    
     /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function settingsTypeAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageHeads($this->getAjaxResponse());
        switch ($this->getParam('type')) {
            case 'setting':
                $result = $manage->setting();
                break;
            default:
                break;
        }
        
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Deductions - ' . $page);
        $this->setPageHeading('Account Settings');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('accounts', $this->view->actionUrl('accounts'));
        $breadrumb->add($page);
    }
    
    /**
     * load fmc name
     * 
     * @param int $id
     */
    private function _loadFmc($id)
    {
        $this->view->setVariable('fmc', $this->_fmcModel->selectColumnById('name', $id));
    }
    
    /**
     * load form options
     */
    private function _loadFormOptions()
    {
        $this->view->setVariable('options', AccountsHelper::conversionOptions());
        $this->view->setVariable('blank', [AccountsHelper::CONVERSION_NO, AccountsHelper::CONVERSION_YES]);
    }
    
}
