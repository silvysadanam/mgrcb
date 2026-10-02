<?php
/**
 * Branch Opening Create Form
 * 
 * @category    Branch
 * @package     Branch\Form
 */

namespace Application\Form\Opening;

use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{ CloseoutDetails, CloseoutData, RPHeads, RPTransactionDetails, Fmc};
use Shared\Utility\AccountSettings;
use Shared\Utility\Transactions;
        //Import PHPMailer classes into the global namespace
        //These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class CreateCloseoutForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var \Model\DeductionMemberDaily
     */
    private $_deductionMemberDailyModel;
    
    /**
     * 
     * @var \Model\DeductionMemberMonthly
     */
    private $_deductionMemberMonthlyModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_entry;
    
    /**
     * 
     * @var Web\Database\Sql
     */
    private $_sql;
    
    private $_fmcid;
    
   
    private $_rpHeadModel;
    private $closeoutDataModel;
    private $closeoutDetailsModel;
    private $denominations = [];

    public function __construct($fmcid = 0, $denominations = [])
    {
        parent::__construct();
        $fields = [
            'Cash_endday', 'Cash_computed', 'notes', 'Cash_difference','afive_hundreds', 'atwo_hundreds', 'ahundreds',
            'afifties', 'atwenties', 'atens', 'afives', 'atwos', 'aones'
        ];
        $this->addFields($fields);
        $this->_fmcid = $fmcid;
        $this->_rpHeadModel = new RPHeads();
        $this->closeoutDataModel = new CloseoutData();
        $this->closeoutDetailsModel = new CloseoutDetails();
        $this->denominations = $denominations;
        $this->_sql = $this->closeoutDataModel->getSql();
        $this->_opensql = $this->closeoutDetailsModel->getSql();
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
        
        if ($this->validator->isNull($this->_formValues['Cash_endday'])) {
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
        $this->_sql->whereEqual('type', 'cash');
        $this->_sql->whereEqual('closeout_date', $today);                  
        $where2 = $this->_sql->getWhere();
        $sql = "SELECT id FROM ". $this->closeoutDataModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmcid} and type='cash' and closeout_date ='{$today}'";
        $data = $this->closeoutDataModel->queryRows($sql); 
        $sql = "SELECT id FROM ". $this->closeoutDetailsModel->getTableQuoted() . " `t` "
                    . "  where 1=1 and `t`.`fmc_id` = {$this->_fmcid} and closeout_date ='{$today}'";
        $odata = $this->closeoutDetailsModel->queryRows($sql); 
        $closeoutData['closeout_date'] = $today;
        $closeoutData['type'] = 'cash';
        $closeoutData['name'] = 'Cash';
        $closeoutData['counted_till'] = $this->_formValues['Cash_endday'];
        $closeoutData['expected_till'] = $this->_formValues['Cash_computed'];
        $closeoutData['till_difference'] = $this->_formValues['Cash_difference'];
        $closeoutData['fmc_id'] = $this->_fmcid;  
        $closeoutData['notes'] = $this->_formValues['notes'];
         
        if(isset($data[0]->id)){           
            $this->closeoutDataModel->update($closeoutData, $where2, 1);
            $this->sendMail($closeoutData);
        } else {
             $this->closeoutDataModel->insert($closeoutData);
             $this->sendMail($closeoutData);
        }
        
        $queryVals		= '';
        $openData = array();
        $openData['closeout_date'] = $today;
        $this->_opensql->clear();
        $this->_opensql->whereEqual('closeout_date', $today);                  
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
            $this->closeoutDetailsModel->update($openData, $where3, 1);
        } else {
            $openData['fmc_id'] = $this->_fmcid;  
            $this->closeoutDetailsModel->insert($openData);
        }
    }
   
    public function sendMail($closeoutData = [])
    {
        //require_once('class.phpmailer.php');
        require __DIR__ . '/../../phpmailer/src/Exception.php';
        require __DIR__ . '/../../phpmailer/src/PHPMailer.php';
        require __DIR__ . '/../../phpmailer/src/SMTP.php';
        //Get FMC Name
        $fmcModel = new Fmc();
        $today = date('d-m-Y H:i:s');
        $_fmc = $fmcModel->find($closeoutData['fmc_id']);
        $body = "<html><head>"
                . "        <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .title { font-size: 18px; font-weight: bold; color: #0066cc; }
                        .info { margin-top: 10px; }
                        .table {
                            width: 100%;
                            margin-bottom: 1rem;
                            color: #6b6f80;
                            background-color: transparent;
                        }
                        .table-bordered {
                            border: 1px solid #e3ebf3;
                        }
            </style></head><body><table class=\"table table-bordered\">";
        $body .= "<tbody>
             <tr>
                <td colspan=\"2\" class=\"title\">{$_fmc->name}</td>                                                           
            </tr>
            <tr>
                <td colspan=\"2\"><b>Closeout Details</b></td>                                                           
            </tr>
            <tr>
                <td>Date</td>
                <td>{$today}</td>
            </tr>
            <tr>
                <td>Payment</td>
                <td>Cash</td>
            </tr>
            <tr>
                <td>End-day Count</td>
                <td>{$closeoutData['counted_till']}</td>
            </tr>
            <tr>
                <td>Total Computed</td>
                <td>{$closeoutData['expected_till']}</td>
            </tr>";
        $body .= "<tr>
                <td>Over/Short</td>";
                if ($closeoutData['till_difference'] < 0){
                    $body .= " <td style=\"color: red;\">{$closeoutData['till_difference']}</td>";
                } else {
                     $body .= " <td style=\"color: blue;\">{$closeoutData['till_difference']}</td>";
                }
        $body .="</tr>
              <tr>
                <td>Notes</td>
                <td>{$closeoutData['notes']}</td>
            </tr>
            </tbody>
            </table></body></html>
            ";
        $mail = new PHPMailer(true);
        $mail->IsSMTP();
        $mail->SMTPDebug  = 0;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";    // try tls if ssl fails
        $mail->Host       = "mail.fwskollam.in";
        $mail->Port       = 465;      // try 587 for TLS
        $mail->Username   = "info@fwskollam.in";
        $mail->Password   = "Sams#ohny2";

        $mail->SetFrom('info@fwskollam.in', 'FWS');
        //$mail->addReplyTo('sajangeorge@gmail.com', 'Sajan George');
        $mail->isHTML(true);  
        $mail->Subject = "Closeout Details of {$_fmc->name}";
        $mail->Body = $body;

        $mail->AddAddress('sajangeorge@gmail.com', 'Zen Infotech');
        $mail->addAddress('samsonjohny@gmail.com', 'Samson Johny');
        $mail->addAddress('benbsingh@gmail.com', 'Ben B Singh');

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "<div class=\"success\"> Contact form submitted! <strong>We will be in touch soon.</strong> </div>";
        }
        $mail->ClearAddresses();
        $mail->ClearAttachments();
        $mail->ClearBCCs();
    }
   
}
