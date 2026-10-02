<?php
/**
 * Application DcbExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\DcbStatement;

use Web\Utility\CsvWriter;


class DcbExport extends DcbStatement
{
    const BLANK = '';
    const PERCENTAGE = '%';
    
    /**
     * 
     * @var \Web\Utility\CsvWriter
     */
    private $_writer;
    
    /**
     * 
     * @var \stdClass
     */
    private $_loan;
   
    /**
     * 
     * @var array
     */
    private $_default;
    
    /**
     * 
     * @var array
     */
    private $_direct = [];
    
    /**
     * 
     * @var \Application\Utility\LoanStore
     */
    private $_store;
    
    /**
     * 
     * @var \Application\Utility\DcbColumn
     */
    private $_dcbColumn;
    
    /**
     * 
     * @var type
     */
    private $_row = [];
    
    /**
     * 
     * @param \Web\View\ViewModel $view
     */
    public function __construct($view)
    {
        parent::__construct($view);
        $this->_dcbColumn = new DcbColumn();
    }
    
    /**
     * set store
     * 
     * @param \Application\Utility\LoanStore $store
     */
    public function store($store)
    {
        $this->_store = $store;
        $this->_loan = $this->_store->loan();
    }

    /**
     * run
     */
    public function run()
    {
        $this->_prepare();
        $fmc = $this->_store->fmc();
        $member = $this->_store->member();
        $titleItems = [
            'DCB', $this->_loan->id, $this->_loan->account_number, $fmc->code, $member->code, $this->_store->loanSource(), 
            $this->view->formatDateTime('d-m-Y', $this->_loan->loan_date), 
            $this->_store->loanType(), intval($this->_loan->loan_amount),
            'as-on', $this->view->formatDateTime('d-m-Y'),
        ];
        
        $title = implode('-', $titleItems);
        $this->_writer = new CsvWriter($title);
        $this->_titleRows();
        $this->_dcb();
        $this->_footer();
        $this->_writer->finish();
    }
    
    
    /**
     * prepare setup
     */
    private function _prepare()
    {
        $this->_default = array_fill(1, $this->columns(), self::BLANK);
        $columns = $this->_dcbColumn->get();
        $this->_direct[0] = [
            DcbColumn::C3 => $columns[DcbColumn::C3]['field'],
            DcbColumn::C4 => $columns[DcbColumn::C4]['field'],
            DcbColumn::C5 => $columns[DcbColumn::C5]['field'],
            DcbColumn::C6 => $columns[DcbColumn::C6]['field'],
            DcbColumn::C7 => $columns[DcbColumn::C7]['field'],
            DcbColumn::C8 => $columns[DcbColumn::C8]['field'],
            DcbColumn::C9 => $columns[DcbColumn::C9]['field'],
            DcbColumn::C10 => $columns[DcbColumn::C10]['field'],
            DcbColumn::C11 => $columns[DcbColumn::C11]['field'],
            DcbColumn::C12 => $columns[DcbColumn::C12]['field'],
            DcbColumn::C13 => $columns[DcbColumn::C13]['field'],
            DcbColumn::C14 => $columns[DcbColumn::C14]['field'],
            DcbColumn::C15 => $columns[DcbColumn::C15]['field'],
            DcbColumn::C16 => $columns[DcbColumn::C16]['field'],
            DcbColumn::C17 => $columns[DcbColumn::C17]['field'],
            DcbColumn::C18 => $columns[DcbColumn::C18]['field'],
            DcbColumn::C19 => $columns[DcbColumn::C19]['field'],
            DcbColumn::C20 => $columns[DcbColumn::C20]['field'],
        ];
        
        $this->_direct[1] = [
            DcbColumn::C22 => $columns[DcbColumn::C22]['field'],
            DcbColumn::C23 => $columns[DcbColumn::C23]['field'],
            DcbColumn::C24 => $columns[DcbColumn::C24]['field'],
            DcbColumn::C25 => $columns[DcbColumn::C25]['field'],
            DcbColumn::C26 => $columns[DcbColumn::C26]['field'],
            DcbColumn::C27 => $columns[DcbColumn::C27]['field'],
            DcbColumn::C28 => $columns[DcbColumn::C28]['field'],
            DcbColumn::C29 => $columns[DcbColumn::C29]['field'],
        ];
    }
    
    /**
     * write title rows
     */
    private function _titleRows()
    {
        $this->_firstRow();
        $this->_secondRow();
        $this->_thirdRow();
        $this->_fourthRow();
        $this->_fifthRow();
    }
    
    /**
     * first row
     */
    private function _firstRow()
    {
        $loan = $this->_store->loan();
        $heading = DcbColumn::HEADING_MAIN;
        $heading .= ' - Loan A/C: ' . $loan->account_number . ' - Status: '
                  . $this->_store->status();
        $this->_write([DcbColumn::HEADING_MAIN]);
    }
    
    /**
     * second row
     */
    private function _secondRow()
    {
        $fmc = $this->_store->fmc();
        $member = $this->_store->member();
        $row = [
            DcbColumn::HEADING_FMC, $fmc->code, self::BLANK, 
            DcbColumn::HEADING_MEMBER_NAME, self::BLANK, $member->name, self::BLANK,
            DcbColumn::HEADING_SOURCE, $this->_store->loanSource(),
            DcbColumn::HEADING_MEMBER_CODE, $member->code,
            DcbColumn::HEADING_DATE, $this->view->datepicker($this->_loan->loan_date),
            self::BLANK,
            DcbColumn::HEADING_AMOUNT, $this->view->amount($this->_loan->loan_amount),
            DcbColumn::HEADING_TENURE, $this->_loan->tenure,
            DcbColumn::HEADING_PENAL_INTEREST, 
            $this->view->amount($this->_loan->penal_interest_rate) . self::PERCENTAGE,
            DcbColumn::HEADING_INTEREST_RATE, 
            $this->view->amount($this->_loan->interest_rate) . self::PERCENTAGE,
            DcbColumn::HEADING_LOAN_TYPE, $this->_store->loanType(), self::BLANK,
            DcbColumn::HEADING_EMI, $this->view->currency($this->_loan->emi),
            'ID', $this->_store->id(),
        ];
        
        $this->_write($row);
    }
    
    /**
     * third row
     */
    private function _thirdRow()
    {
        $this->_row = [ DcbColumn::HEADING_SCHEDULED_DEMAND ];
        $this->_blank(6);
        $this->_row[] = DcbColumn::HEADING_ARREARS_DEMAND;
        $this->_blank(3);
        $this->_row[] = DcbColumn::HEADING_TOTAL_DEMAND_CSV;
        $this->_row[] = DcbColumn::HEADING_COLLECTION;
        $this->_blank(8);
        $this->_row[] = DcbColumn::HEADING_BALANCE;
        $this->_blank(5);
        $this->_row[] = DcbColumn::HEADING_AGEING;
        $this->_blank();
        $this->_write();
    }
    
    /**
     * fourth row
     */
    private function _fourthRow()
    {
        $columns = $this->_dcbColumn->get();
        foreach ($columns as $id => $item) {
            $this->_row[] = $item['title'];
            if (DcbColumn::C27 == $id) {
                break;
            }
        }
        
        $this->_write();
    }
    
    /**
     * fifth row
     */
    private function _fifthRow()
    {
        for ($count = 1; $count < 30; $count++) {
            $this->_row[] = $count;
        }
        
        $this->_write();
    }
    
    /**
     * write DCB entries
     */
    private function _dcb()
    {
        $schedule = $this->_store->schedule();
        $scheduleCatch = $this->_store->scheduleCatch();
        foreach ($schedule as $instalment) {
            $this->_row = $this->_default;
            if ($instalment->has_dcb_entry) {
                $this->_row[DcbColumn::C1] = $instalment->instalment;
                $this->_row[DcbColumn::C2] = $this->view->instalmentDate($instalment->instalment_date);
                foreach ($this->_direct[0] as $key => $column) {
                    $this->_row[$key] = $instalment->{$column};
                }
                //$this->_row[DcbColumn::C21] = $this->view->integer($instalment->percentage_catch);
                foreach ($this->_direct[1] as $index => $field) {
                    $this->_row[$index] = $instalment->{$field};
                }
                $this->_row[DcbColumn::C20] = $this->view->integer($scheduleCatch[$instalment->id]);
                $this->_row[DcbColumn::C21] = $this->view->percentage($instalment->collection_repaid, $scheduleCatch[$instalment->id]);
            } else {
                $this->_row[DcbColumn::C25] = $instalment->closing_principal;
            }
            
            $this->_write();
        }
    }
    
    /**
     * write footer
     * 
     * @return null
     */
    private function _footer()
    {
        $this->_write([self::BLANK]);
        $summary = $this->_store->summary();
        $this->_row = $this->_default;
        $valueColumns = [
            DcbColumn::C4, DcbColumn::C5, DcbColumn::C6, DcbColumn::C8,
            DcbColumn::C9, DcbColumn::C10, DcbColumn::C11, DcbColumn::C12,
            DcbColumn::C13, DcbColumn::C14, DcbColumn::C15, DcbColumn::C16,
            DcbColumn::C17, DcbColumn::C18, DcbColumn::C19, 
        ];
        
        if (empty($summary)) {
            foreach ($valueColumns as $column) {
                $this->_row[$column] = '0.00';
            }
            $this->_row[DcbColumn::C21] = '0';
        } else {
            $columns = $this->_dcbColumn->get();
            foreach ($valueColumns as $id) {
                $this->_row[$id] = $summary->{$columns[$id]['field']};
            }
            
            $loanCatch = $this->_store->loanCatch();
            $this->_row[DcbColumn::C20] = $this->view->integer($loanCatch);
            $this->_row[DcbColumn::C21] = $this->view->percentage($loanCatch, $summary->collection_repaid);
            $this->_row[DcbColumn::C27] = $this->view->amount($summary->balance_actual_principal_interest_outstanding - $summary->scheduled_demand_principal_outstanding);
        }
        
        $this->_write();
    }
    
    /**
     * add blank cells
     * 
     * @param int $cells
     */
    private function _blank($cells = 1)
    {
        for ($count = 0; $count < $cells; $count++) {
            $this->_row[] = self::BLANK;
        }
    }
    
    /**
     * write row
     * 
     * @param array $row
     * @return null
     */
    private function _write($row = null)
    {
        if (null !== $row) {
            $this->_writer->row($row);
            return;
        }
        
        if (! empty($this->_row)) {
            $this->_writer->row($this->_row);
            $this->_row = [];
        }
    }
    
}
