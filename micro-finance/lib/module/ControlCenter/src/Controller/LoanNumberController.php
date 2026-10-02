<?php
/**
 * ControlCenter LoanNumber controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use Application\Utility\LoanHelper;

use ControlCenter\ControlCenterController;


class LoanNumberController extends ControlCenterController
{
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->stop();
    }
    
    public function importAction()
    {
        $this->start('ControlCenter / Loan Number / import');
        $mf = LoanHelper::LOAN_SOURCE_MF;
        $loanId = 1831;
        $limit = 100;
        $loanModel = new \Model\Loan();
        $sql = $loanModel->getSql();
        $sql->whereGreater('id', $loanId)
            ->whereId('loan_source_id', $mf);
        $where = $sql->getWhere();
        if (0 == $loanModel->selectTotal($where)) {
            $this->_printLine('No loans');
            $this->finish();
        }
        
        $memberModel = new \Model\Member();
        $members = [];
        
        $fields = ['id', 'account_number', 'loan_date', 'member_id'];
        
        $loans = $loanModel->selectFields($fields, $where, 'id', $limit);
        
        $loanNumberModel = new \Model\LoanNumber();
        $default = [
            'loan_source_id' => $mf,
            'loan_id' => 0,
            'loan_account' => '',
            'loan_number' => 0,
            'loan_year' => 0,
            'fmc_id' => 0,
        ];
        
        $import = $loanNumberModel->getInsertPreparedStatement(array_keys($default));
        
        try {
            $loanModel->getAdapter()->beginTransaction();
            foreach ($loans as $loan) {
                $this->_printLine($loan->id . ', ' . $loan->account_number);
                if ($loanNumberModel->checkByColumnId('loan_id', $loan->id)) {
                    $this->_printLine('Already imported');
                } else {
                    $extracted = $this->_extract($loan->account_number);
                    if (empty($extracted)) {
                        $this->_printLine('No match');
                    } else {
                        $insert = $default;
                        $insert['loan_id'] = $loan->id;
                        $insert['loan_account'] = $loan->account_number;
                        $insert['loan_number'] = $extracted[0];
                        $insert['loan_year'] = $extracted[1];
                        
                        if (! isset($members[$loan->member_id])) {
                            $members[$loan->member_id] = $memberModel->selectColumnById('fmc_id', $loan->member_id);
                        }
                        $insert['fmc_id'] = $members[$loan->member_id];
                        $import->execute(array_values($insert));
                        $this->_printLine('Imported');
                    }
                }
            }
            
            $loanModel->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $loanModel->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $loanModel->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\ErrorException $ex) {
            $loanModel->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
        
    }
    
    private function _extract($number)
    {
        
        if ('' == $number) {
            return false;
        }
        
        $extracted = [];
        if (0 === strpos($number, 'MF')) {
            $p1 = explode('/', $number);
            if (2 == count($p1)) {
                if (2 == strlen($p1[1])) {
                    $year = '20' . $p1[1];
                } else {
                    $year = $p1[1];
                }
                
                $account = preg_replace('/[^\d]+/', '', $p1[0]);
                $extracted = [(int) preg_replace('/^0+/', '', $account), $year];
            }
        }
        
        return $extracted;
    }
    
}
