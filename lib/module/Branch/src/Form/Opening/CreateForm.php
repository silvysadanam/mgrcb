<?php
/**
 * Branch Opening Create Form
 * 
 * @category    Branch
 * @package     Branch\Form
 */

namespace Branch\Form\Opening;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{ OpeningDetails, CloseoutData, RPHeads, RPTransactionDetails};
use Shared\Utility\AccountSettings;
use Shared\Utility\Transactions;

class CreateForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;    

    private $_sql;    
    
    private $_fmcid;    
   
    private $_rpHeadModel;
    private $closeoutDataModel;
    private $openingDetailsModel;
    private $denominations = [];

    public function __construct($fmcid = 0, $denominations = [])
    {
        parent::__construct();
        $fields = [
            'counted_till', 'expected_till', 'notes', 'till_difference','afive_hundreds', 'atwo_hundreds', 'ahundreds',
            'afifties', 'atwenties', 'atens', 'afives', 'atwos', 'aones'
        ];
        $this->addFields($fields);
        $this->_fmcid = $fmcid;
        $this->_rpHeadModel = new RPHeads();
        $this->closeoutDataModel = new CloseoutData();
        $this->openingDetailsModel = new OpeningDetails();
        $this->denominations = $denominations;
        $this->_sql = $this->closeoutDataModel->getSql();
        $this->_opensql = $this->openingDetailsModel->getSql();
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
        
        if ($this->validator->isNull($this->_formValues['counted_till'])) {
            $this->error->required('Counted Till');
        }
        
        return $this->noError();
    }
    
    /**
     * create deduction bill
     * 
     * @return int
     */
    public function create($userId)
    {
        //print_r($this->_formValues);exit;
        $today = date("Y-m-d");
        $this->_sql->clear();
        $this->_sql->whereEqual('type', 'counted_till');
        $this->_sql->whereEqual('closeout_date', $today);     
        $this->_sql->whereEqual('fmc_id', $this->_fmcid); 
        $where2 = $this->_sql->getWhere();
        $sql = "SELECT id FROM ". $this->closeoutDataModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmcid} and closeout_date ='{$today}'";
        $data = $this->closeoutDataModel->queryRows($sql); 
        $sql = "SELECT id FROM ". $this->openingDetailsModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmcid} and opening_date ='{$today}'";
        $odata = $this->openingDetailsModel->queryRows($sql); 
        $closeoutData['closeout_date'] = $today;
        $closeoutData['type'] = 'counted_till';
        $closeoutData['name'] = 'Counted Till';
        $closeoutData['counted_till'] = $this->_formValues['counted_till'];
        $closeoutData['expected_till'] = $this->_formValues['expected_till'];
        $till_difference = $this->_formValues['expected_till'] - $this->_formValues['counted_till'];
        $closeoutData['till_difference'] = $till_difference;
        //$closeoutData['till_difference'] = $this->_formValues['till_difference'];
        $closeoutData['fmc_id'] = $this->_fmcid;   
        $closeoutData['notes'] = $this->_formValues['notes'];
        if(isset($data[0]->id)){
            $this->closeoutDataModel->update($closeoutData, $where2, 1);
        } else {
             $this->closeoutDataModel->insert($closeoutData);
        }
        
        $queryVals		= '';
        $openData = array();
        $openData['opening_date'] = $today;
        $this->_opensql->clear();
        $this->_opensql->whereEqual('opening_date', $today);   
        $this->_opensql->whereEqual('fmc_id', $this->_fmcid);
        $where3 = $this->_opensql->getWhere();
        if($this->denominations)foreach($this->denominations as $denomination => $value){
                $denominationVal = 0;
                $name = 'cash_'.$denomination;
                $rname = 'a'.$denomination;
                if (isset($this->_formValues[$rname])) {
                       if($this->_formValues[$rname] != ''){
                        $denominationVal	= intval($this->_formValues[$rname]);
                        $queryVals	.= ", $name=$denominationVal";
                        $openData[$name] = $denominationVal;
                       }
                }
        }
       if(isset($odata[0]->id)){
            $this->openingDetailsModel->update($openData, $where3, 1);
        } else {
            $openData['fmc_id'] = $this->_fmcid;  
            $this->openingDetailsModel->insert($openData);
        }
       
        
    }
}
