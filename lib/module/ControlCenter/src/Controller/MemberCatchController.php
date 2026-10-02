<?php
/**
 * ControlCenter MemberCatch controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;
use Web\Core\Session;


class MemberCatchController extends ControlCenterController
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
    
    /**
     * initial action
     * 
     * @return \Web\View\ViewModel
     */
    public function updateAction()
    {
        $this->start('ControlCenter / MemberCatch / update');
        
        $id = 0;
        if (Session::is('id')) {
            $id = Session::get('id');
        }
        
        $this->_printLine('Last ID ' . $id);
        $auctionMonthlyModel = new \Model\AuctionMonthly();
        $model = new \Model\AuctionChit();
        $memberModel = new \Model\Member();
        $sql = $memberModel->getSql();
        $sql->whereGreater('member_id', $id);
        $where = $sql->getWhere();
        if (0 == $auctionMonthlyModel->selectTotal($where)) {
            $this->_printLine('No member entries');
            $this->finish();
        }
        
        $sql->clear();
        $sql->whereGreater('id', $id)
            ->whereId('member_type_id', 1);
        $memberIds = $memberModel->selectIds($sql->getWhere(), 'id', 25);
        $fields = ['entries', 'amount', 'quantity', 'advance'];
        $from = 'FROM ' . $model->getTableQuoted() . ' WHERE `member_id` =? '
                . 'AND `auction_date` BETWEEN ? AND ?';

        $query1 = 'SELECT COUNT(`id`) AS `entries`, SUM(`amount`) AS `amount`, '
                . 'SUM(`quantity`) AS `quantity`, SUM(`advance`) AS `advance` '
                . $from;
        $summary = $model->prepare($query1);

        $query2 = 'SELECT COUNT(DISTINCT `auction_date`) AS `auction_days` ' . $from;
        $days = $model->prepare($query2);
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($memberIds as $memberId) {
                $this->_printLine('Member ID ' . $memberId);
                $sql->clear();
                $sql->whereId('member_id', $memberId);
                $whereMember = $sql->getWhere();
                if (0 == $auctionMonthlyModel->selectTotal($whereMember)) {
                    $this->_printLine('No monthly entries');
                    continue;
                }

                $list = $auctionMonthlyModel->select($whereMember, 'auction_date');
            
                foreach ($list as $item) {
                    //$this->_printArray($item);
                    $dates = $this->_getMonth($item->auction_date);
                    $this->_printArray($dates);
                    $params = [$memberId, $dates[0], $dates[1]];
                    //$this->_printArray($params);
                    $summary->execute($params);
                    $result1 = $summary->fetch();
                    $summary->closeCursor();
                    if ($result1->entries > 0) {
                        //$this->_printArray($result1);
                        $days->execute($params);
                        $result2 = $days->fetchColumn();
                        //$this->_printLine($result2);
                        $days->closeCursor();
                        $update = [];
                        foreach ($fields as $column) {
                            if ($item->{$column} != $result1->{$column}) {
                                $update[$column] = $result1->{$column};
                            }
                        }
                        if ($result2 != $item->auction_days) {
                            $update['auction_days'] = $result2;
                        }
                        $balance = $result1->amount - $result1->advance;
                        if ($balance != $item->balance) {
                            $update['balance'] = $balance;
                        }

                        if (! empty($update)) {
                            $this->_printArray($item);
                            $this->_printLine('Updating');
                            $this->_printArray($update);
                            $auctionMonthlyModel->updateById($update, $item->id);
                        } else {
                            $this->_printLine('No changes');
                        }
                    } else {
                        $this->_printLine('No auction chit entry found for ID - ' . $item->id);
                        $auctionMonthlyModel->deleteById($item->id);
                    }
                }
                
            }
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\ErrorException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
        
        Session::set('id', $memberId);
        $this->_printLine('Completed');
        $this->finish();
    }
    
    private function _getMonth($date)
    {
        $dateTime = DateHelper::getDateTime($date);
        $year = $dateTime->format('Y');
        $month = $dateTime->format('n');
        $dateTime->setDate($year, $month, 1);
        $start = $dateTime->format(DateHelper::MYSQL_DATE);
        
        return [$start, $date];
    }
    
    private function _printArray($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
    
}
