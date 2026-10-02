<?php
/**
 * ControlCenter DCB Restructure controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use Application\Utility\{DcbCreator, LoanStore};

use ControlCenter\ControlCenterController;

use Model\Loan;


class DcbrestructureController extends ControlCenterController
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
        echo 'in';
        $this->stop();
    }
    
    /**
     * pqam action
     * 
     */
    public function runAction()
    {
        $this->start('ControlCenter / Dcbrestructure / run');
        $offset = 600;
        $limit = 100;
        $this->_printLine('Offset: ' . $offset . ', Limit: ' . $limit);
        $model = new Loan();
        $sql = $model->getSql();
        $sql->whereZero('closed');
        $where = $sql->getWhere();
        $total = $model->selectTotal($where);
        $this->_printLine('Total: ' . $total);
        $list = $model->selectIds($where, 'id', $offset . ',' . $limit);
        if (empty($list)) {
            $this->_printLine('No pending records');
            $this->finish();
        }
        
        $this->_printLine('Selected: ' . count($list));

        foreach ($list as $loanId) {
            $store1 = new LoanStore($loanId);
            //$this->_printLine('ID: ' . $loanId);
            echo $loanId;
            if ($store1->hasDcb()) {
                //$this->_printLine('Has DCB');
                $schedule = $store1->schedule();
                $entries = [];
                foreach ($schedule as $item) {
                    if (0 == $item->has_dcb_entry) {
                        break;
                    }
                    
                    $entries[$item->id] = [$item->collection_repaid, $item->catch_amount, $item->instalment];
                }
                
                try {
                    $model->getAdapter()->beginTransaction();
                    $store1->resetDcb();
                    foreach ($entries as $instalment) {
                        echo ' ' . $instalment[2];
                        $store = new LoanStore($loanId);
                        $creator = new DcbCreator($store);
                        $creator->repayment($instalment[0])
                                ->catchAmount($instalment[1]);
                        if (! $creator->checkDemand()) {
                            echo ' - Collection exceeded';
                            break;
                        }
        
                        $creator->run();
                        //echo ' - Y, ';
                    }
                    echo ' - Y, ';
                    $model->getAdapter()->commit();
                } catch (\PDOException $ex) {
                    $model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                } catch (\Web\Exception $ex) {
                    $model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                } catch (\Exception $ex) {
                    $model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                } catch (\ErrorException $ex) {
                    $model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                }
                
            } else {
                //$this->_printLine('No DCB');
                echo ' - N, ';
            }
        }

        $this->_printLine('Completed');
        $this->finish();
    }
    
    public function loanCatchAction()
    {
        $this->start('ControlCenter / Dcbrestructure / run');
    }
    
}
