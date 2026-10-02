<?php
/**
 * Branch AuctionPayment Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Shared\Utility\AccountSettings;
use Model\AuctioneerAccountDaily;
use Model\{RPTransMemberDetails, RPTransMember, Member};
use Shared\Utility\AuctioneerAccount;
use Model\{RPHeads};

class AuctionPaymentForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    private $_fmcid;
     
    public function __construct($fmcid = 0)
    {
        parent::__construct();
        $this->_fmcid = $fmcid;
        $this->_headModel = new RPHeads();
        $fields = ['payment_date', 'donor_id', 'amount', 'description', 'notes'];
        $this->addFields($fields);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        
        if ($this->validator->isNull($this->_formValues['payment_date'])) {
            $this->error->notSelected('Date');
        }
        
        if ($this->validator->notSelected($this->_formValues['donor_id'])) {
            $this->error->notSelected('Donor');
        }
        
        if ($this->validator->isNull($this->_formValues['amount'])) {
                $this->error->add('Amount required');
        } elseif (! $this->validator->nonZeroInteger($this->_formValues['amount'])) {
            $this->error->add('Amount is invalid');
        }
        
        if ($this->noError()) {
            $sql = $this->getModel()->getSql();
            $sql->whereGreater('payment_date', DateHelper::fromDatepicker($this->_formValues['payment_date']))
                ->whereId('donor_id', $this->_formValues['donor_id']);
            if ($this->getModel()->selectTotal($sql->getWhere()) > 0) {
                $this->error->add('Payment exists after selected date');
            }    
        }
        
        if ($this->noError()) {
            $closingBalance = $this->_getBalance();
            if ($this->_formValues['amount'] > $closingBalance) {
                $this->error->add('Amount exceeds closing balance');
            }
        }
        
        return $this->noError();
    }
    
    public function getHeadID($headData, $fmcid = null)
    {
         //Account entries start here
        $headName = $headData['name'];
        $heads = [];
        $headCode = $headData['code'];
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where code= '{$headCode}' and transType = '{$headData['transType']}'  and fmc_id = {$fmcid}  order by head_name";
        $rpheads = $this->_headModel->queryRows($headQuery);   
        if(isset($rpheads[0]->id)){
            $heads['id'] = $rpheads[0]->id;
            $heads['name'] = $rpheads[0]->head_name;
        } else {
            //create new head
            $insert = [];
            $insert['head_name'] = $headName;
            $insert['head_alias'] = $headName;
            $insert['code'] = $headData['code'];
            $insert['transType'] = $headData['transType'];
            $insert['opb'] = 0;
            $insert['fmc_id'] = $fmcid;
            $insert['status'] = 'N';
            $heads['id'] = $this->_headModel->insert($insert);
            $heads['name'] = $headName;
        }
        return $heads;
    }
    
    public function auctionCollectionTransactions($managerId)
    {
        $headData = [];
        $memberModel = new Member();
        $member = $memberModel->find($this->_formValues['donor_id']);
        $name = explode(" ", $member->name);
        $options = new AccountSettings($this->_fmcid);
        $options->load();
        $optionReceiptNo = $options->receiptNo();
        $currentReceiptNo = $options->getNextReceiptNo();
        if(!$currentReceiptNo){
            $receiptNo = $optionReceiptNo;
        }else {
            if($optionReceiptNo > $currentReceiptNo){
                $receiptNo = $optionReceiptNo;
            } else {
                $receiptNo = $currentReceiptNo + 1;
            }
        }
            //Auction collection
        $headName = "{$member->code}-".$name[0]."-Auct Collection";
        $headData['name'] = $headName;
       // $headData['name'] = "{$code}-Auctioneer Collection";
        $headData['code'] = $member->code;
        $headData['transType'] = 'Direct Income';
         // Payment Entries
        $heads = $this->getHeadID($headData, $this->_fmcid);
         // Payment Entries
        $today = DateHelper::today();
        $masterModel = new RPTransMember();
        $insert = [
            'receiptNo' => $receiptNo,
           // 'action_chit_id' => 99999,
            'fmc_id' => $this->_fmcid,
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => 0,
            'transType' =>'R',
            'transBy' => 0,
            'remarks' =>  $this->_formValues['description'],
            'created_by' => $managerId,
            'transDate' => DateHelper::fromDatepicker($this->_formValues['payment_date'])
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        $insert = [];
        $insert = [
            'transID' => $transid,
            'headID' => $heads['id'],
            'amount' => $this->_formValues['amount'],
            'remarks' => $heads['name']
        ];
        $dataModel->insert($insert); 
    }
    /**
     * create auction chit
     * 
     * @param int $managerId
     * @return int
     */
    public function create($managerId)
    {
        $now = DateHelper::timestamp();
        $insert = [
            'payment_date' => DateHelper::fromDatepicker($this->_formValues['payment_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'amount' => $this->_formValues['amount'],
            'description' => $this->_formValues['description'],
            'notes' => $this->_formValues['notes'],
            'created_by' => $managerId,
            'created_at' => $now,
        ];
        
        $id = $this->save($insert);
        $this->auctionCollectionTransactions($managerId);
        $account = new AuctioneerAccount($this->_formValues['donor_id']);
        $account->run($insert['payment_date']);
        
        return $id;
    }
    
    /**
     * get balance
     * 
     * @return int
     */
    private function _getBalance()
    {
        $balance = 0;
        $sql = $this->getModel()->getSql();
        $sql->whereId('donor_id', $this->_formValues['donor_id'])
            ->whereLess('payment_date', DateHelper::fromDatepicker($this->_formValues['payment_date']), true);
        $where = $sql->getWhere();
        
        $dailyModel = new AuctioneerAccountDaily();
        if ($dailyModel->selectTotal($where) > 0) {
            $last = $dailyModel->selectRow($where, 'payment_date DESC');
            //$balance = $dailyModel->selectColumn('closing_balance', $where, 'payment_date DESC');
            $balance = $last->closing_balance;
        }
        
        return $balance;
    }
    
    
    
}
