<?php
/**
 * Application PqaLoanExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\CsvExport;
use Application\Generator\LoanAnalysis;

use Shared\Column\PqaLoan;


class PqaLoanExport extends CsvExport
{
    /**
     * 
     * @var \Application\Utility\PqaGenerator
     */
    private $_generator;
    
    /**
     * 
     * @param \Web\View\ViewModel $view
     */
    public function __construct($view)
    {
        parent::__construct($view);
        
        $this->cells(new PqaLoan());
    }

    /**
     * run
     * 
     * @param \Application\Generator\LoanAnalysis $generator
     */
    public function run(LoanAnalysis $generator)
    {
        $this->_generator = $generator;
        $this->_prepare();
        $title = 'Credit Portfolio Analysis - Loans Level';
        $this->loadWriter($title);
        $this->_titleRows();
        $this->_writeEntries();
        $this->_writeGrandTotal();
        $this->_writer->finish();
    }
    
    
    /**
     * prepare setup
     */
    private function _prepare()
    {
        $this->_direct = [
            PqaLoan::C13 => $this->_cells[PqaLoan::C13]['field'],
            PqaLoan::C14 => $this->_cells[PqaLoan::C14]['field'],
            PqaLoan::C15 => $this->_cells[PqaLoan::C15]['field'],
            PqaLoan::C16 => $this->_cells[PqaLoan::C16]['field'],
            PqaLoan::C17 => $this->_cells[PqaLoan::C17]['field'],
            PqaLoan::C18 => $this->_cells[PqaLoan::C18]['field'],
            PqaLoan::C19 => $this->_cells[PqaLoan::C19]['field'],
            PqaLoan::C20 => $this->_cells[PqaLoan::C20]['field'],
            PqaLoan::C21 => $this->_cells[PqaLoan::C21]['field'],
            PqaLoan::C22 => $this->_cells[PqaLoan::C22]['field'],
            PqaLoan::C23 => $this->_cells[PqaLoan::C23]['field'],
            PqaLoan::C24 => $this->_cells[PqaLoan::C24]['field'],
            PqaLoan::C25 => $this->_cells[PqaLoan::C25]['field'],
            PqaLoan::C26 => $this->_cells[PqaLoan::C26]['field'],
        ];
    }
    
    /**
     * write title rows
     */
    private function _titleRows()
    {
        $this->_write([PqaLoan::HEADING_MAIN]);
        $filters = $this->_generator->filters();
        $this->_write([$filters['fmc'] , ' Level']);
        $this->_write(['Source: ' . $filters['type']]);
        $this->_write(['Type: ' . $filters['type']]);
        $this->_write(['Loan Amount Range: ' . $filters['amount']]);
        $this->_write(['Ageing: ' . $filters['ageing']]);
        $this->_write(['As On: ' . $filters['date']]);
        $this->_columnTitles();
        $this->_columnNumbers();
    }
    
    /**
     * column titles
     */
    private function _columnTitles()
    {
        $row = [
            $this->blank(),
            $this->_cells[PqaLoan::MEMBER]['title'],
            $this->_cells[PqaLoan::CODE]['title'],
            $this->_cells[PqaLoan::FMC]['title'],
            $this->_cells[PqaLoan::LOAN_ACCOUNT]['title'],
            $this->_cells[PqaLoan::LOAN_TYPE]['title'],
            $this->_cells[PqaLoan::LOAN_DATE]['title'],
            $this->_cells[PqaLoan::LOAN_AMOUNT]['title'],
            $this->_cells[PqaLoan::LOAN_SOURCE]['title'],
            $this->_cells[PqaLoan::TENURE]['title'],
            $this->_cells[PqaLoan::INTEREST_RATE]['title'],
            $this->_cells[PqaLoan::EMI]['title'],
            $this->_cells[PqaLoan::INSTALMENT]['title'],
            $this->_cells[PqaLoan::PRINCIPAL_PREPAID]['title'],
            $this->_cells[PqaLoan::PRINCIPAL_ARREARS]['title'],
            $this->_cells[PqaLoan::TOTAL_PRINCIPAL]['title'],
            $this->_cells[PqaLoan::INTEREST_PAID]['title'],
            $this->_cells[PqaLoan::TOTAL_PAID]['title'],
            $this->_cells[PqaLoan::SCHEDULED_OUTSTANDING]['title'],
            $this->_cells[PqaLoan::PRINCIPAL_OUTSTANDING]['title'],
            $this->_cells[PqaLoan::ACTUAL_OUTSTANDING]['title'],
            $this->_cells[PqaLoan::OVERDUE]['title'],
            $this->_cells[PqaLoan::AGEING]['title'],
            $this->_cells[PqaLoan::PAR]['title'],
            $this->_cells[PqaLoan::CATCH_AMOUNT]['title'],
            $this->_cells[PqaLoan::REPAID]['title'],
            $this->_cells[PqaLoan::CATCH_PERCENTAGE]['title'],
        ];
        
        $this->_write($row);
    }
    
    /**
     * fourth row
     */
    private function _columnNumbers()
    {
        $row = [];
        for ($count = 1; $count <= PqaLoan::COLUMNS; $count++) {
            $row[] = $count;
        }
        
        $this->_write($row);
    }
    
    private function _writeEntries()
    {
        $totalFields = [
            PqaLoan::C14 => $this->_cells[PqaLoan::C14]['field'],
            PqaLoan::C15 => $this->_cells[PqaLoan::C15]['field'],
            PqaLoan::C16 => $this->_cells[PqaLoan::C16]['field'],
            PqaLoan::C17 => $this->_cells[PqaLoan::C17]['field'],
            PqaLoan::C18 => $this->_cells[PqaLoan::C18]['field'],
            PqaLoan::C19 => $this->_cells[PqaLoan::C19]['field'],
            PqaLoan::C20 => $this->_cells[PqaLoan::C20]['field'],
            PqaLoan::C21 => $this->_cells[PqaLoan::C21]['field'],
            PqaLoan::C22 => $this->_cells[PqaLoan::C22]['field'],
            PqaLoan::C23 => $this->_cells[PqaLoan::C23]['field'],
            PqaLoan::C24 => $this->_cells[PqaLoan::C24]['field'],
            PqaLoan::C26 => $this->_cells[PqaLoan::C26]['field'],
        ];
        
        $loans = $this->_generator->loans();
        $members = $this->_generator->members();
        $total = $this->_generator->memberTotal();
        $entries = $this->_generator->entries();
        $count = 0;
        foreach ($entries as $memberId => $memberItems) {
            $first = true;
            foreach ($memberItems as $entry) {
                ++$count;
                $this->_row = $this->_default;
                $this->_row[PqaLoan::A] = $count;
                $loan = $loans[$entry->loan_id];
                $this->_row[PqaLoan::E] = $loan->id;
                $this->_row[PqaLoan::F] = $this->view->lookup('type', $loan->loan_type_id);
                $this->_row[PqaLoan::G] = $this->view->datepicker($loan->loan_date);
                $this->_row[PqaLoan::H] = $loan->loan_amount;
                $this->_row[PqaLoan::I] = $this->view->lookup('source', $loan->loan_source_id);
                $this->_row[PqaLoan::J] = $loan->tenure;
                $this->_row[PqaLoan::K] = $this->view->amount($loan->interest_rate) . $this->percentage();
                $this->_row[PqaLoan::L] = $loan->emi;
                foreach ($this->_direct as $key => $field) {
                    $this->_row[$key] = $entry->{$field};
                }
                
                if ($first) {
                    $member = $members[$memberId];
                    $this->_row[PqaLoan::B] = $member->name;
                    $this->_row[PqaLoan::C] = $member->code;
                    $this->_row[PqaLoan::D] = $this->view->lookup('fmc', $member->fmc_id);
                    //$this->_row[PqaLoan::X] = $total[$memberId]['catch'];
                    //$this->_row[PqaLoan::Z] = $total[$memberId]['percentage_catch'] . $this->percentage();
                    $first = false;
                }
                
                $this->_write();
            }
            
            $this->_row = $this->_default;
            $this->_row[PqaLoan::E] = $total[$memberId]['loans'];
            $this->_row[PqaLoan::H] = $total[$memberId]['amount'];
            $this->_row[PqaLoan::L] = $total[$memberId]['emi'];
            $this->_row[PqaLoan::Y] = $total[$memberId]['catch'];
            $this->_row[PqaLoan::AA] = $total[$memberId]['percentage_catch'] . $this->percentage();
            foreach ($totalFields as $key => $field) {
                $this->_row[$key] = $total[$memberId][$field];
            }
            $this->_write();
        }
    }
    
    private function _writeGrandTotal()
    {
        $grandTotal = $this->_generator->grandTotal();
        $this->_row = $this->_default;
        $this->_row[PqaLoan::B] = 'Grand Total';
        $this->_row[PqaLoan::E] = $grandTotal['loans'];
        $this->_row[PqaLoan::H] = $grandTotal['amount'];
        $this->_row[PqaLoan::L] = $grandTotal['emi'];
        $this->_row[PqaLoan::N] = $grandTotal['principal_prepaid_current'];
        $this->_row[PqaLoan::O] = $grandTotal['principal_arrears'];
        $this->_row[PqaLoan::P] = $grandTotal['principal'];
        $this->_row[PqaLoan::Q] = $grandTotal['interest'];
        $this->_row[PqaLoan::R] = $grandTotal['total'];
        $this->_row[PqaLoan::S] = $grandTotal['scheduled_outstanding'];
        $this->_row[PqaLoan::T] = $grandTotal['principal_outstanding'];
        $this->_row[PqaLoan::U] = $grandTotal['actual_outstanding'];
        $this->_row[PqaLoan::V] = $grandTotal['overdue'];
        $this->_row[PqaLoan::X] = $grandTotal['par'];
        $this->_row[PqaLoan::Y] = $grandTotal['catch'];
        $this->_row[PqaLoan::Z] = $grandTotal['repaid'];
        $this->_row[PqaLoan::AA] = $this->view->percentage($grandTotal['repaid'], $grandTotal['catch']) . $this->percentage();
        $this->_write();
    }
    
}
