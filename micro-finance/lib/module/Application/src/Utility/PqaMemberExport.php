<?php
/**
 * Application PqaMemberExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\CsvExport;
use Application\Generator\MemberAnalysis;

use Shared\Column\PqaMember;


class PqaMemberExport extends CsvExport
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
        $this->cells(new PqaMember());
    }

    /**
     * run
     * 
     * @param \Application\Generator\MemberAnalysis $generator
     */
    public function run(MemberAnalysis $generator)
    {
        $this->_generator = $generator;
        $this->_prepare();
        $this->loadWriter(PqaMember::HEADING_MAIN);
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
            PqaMember::C5 => $this->_cells[PqaMember::C5]['field'],
            PqaMember::C6 => $this->_cells[PqaMember::C6]['field'],
            PqaMember::C7 => $this->_cells[PqaMember::C7]['field'],
            PqaMember::C8 => $this->_cells[PqaMember::C8]['field'],
            PqaMember::C9 => $this->_cells[PqaMember::C9]['field'],
            PqaMember::C10 => $this->_cells[PqaMember::C10]['field'],
            PqaMember::C11 => $this->_cells[PqaMember::C11]['field'],
            PqaMember::C12 => $this->_cells[PqaMember::C12]['field'],
            PqaMember::C13 => $this->_cells[PqaMember::C13]['field'],
            PqaMember::C14 => $this->_cells[PqaMember::C14]['field'],
            PqaMember::C15 => $this->_cells[PqaMember::C15]['field'],
            PqaMember::C16 => $this->_cells[PqaMember::C16]['field'],
            PqaMember::C17 => $this->_cells[PqaMember::C17]['field'],
            PqaMember::C20 => $this->_cells[PqaMember::C20]['field'],
        ];
    }
    
    /**
     * write title rows
     */
    private function _titleRows()
    {
        $this->_write([PqaMember::HEADING_MAIN]);
        $filters = $this->_generator->filters();
        $this->_write([$filters['fmc'] . ' Level']);
        $this->_write(['Total Loan Per Member Range: ' . $filters['amount']]);
        $this->_write(['Ageing: ' . $filters['ageing']]);
        $this->_write(['As On ' . $filters['date']]);
        $this->_columnTitles();
        $this->_columnNumbers();
    }
   
    /**
     * third row
     */
    private function _columnTitles()
    {
        $row = [
            $this->blank(),
            $this->_cells[PqaMember::MEMBER]['title'],
            $this->_cells[PqaMember::CODE]['title'],
            $this->_cells[PqaMember::FMC]['title'],
            $this->_cells[PqaMember::LOANS]['title'],
            $this->_cells[PqaMember::LOAN_AMOUNT]['title'],
            $this->_cells[PqaMember::EMI]['title'],
            $this->_cells[PqaMember::PRINCIPAL_PREPAID]['title'],
            $this->_cells[PqaMember::PRINCIPAL_ARREARS]['title'],
            $this->_cells[PqaMember::PRINCIPAL_PAID]['title'],
            $this->_cells[PqaMember::INTEREST_PAID]['title'],
            $this->_cells[PqaMember::TOTAL_PAID]['title'],
            $this->_cells[PqaMember::SCHEDULED_OUTSTANDING]['title'],
            $this->_cells[PqaMember::PRINCIPAL_OUTSTANDING]['title'],
            $this->_cells[PqaMember::ACTUAL_OUTSTANDING]['title'],
            $this->_cells[PqaMember::OVERDUE]['title'],
            $this->_cells[PqaMember::AGEING]['title'],
            $this->_cells[PqaMember::PAR]['title'],
            $this->_cells[PqaMember::CATCH_AMOUNT]['title'],
            $this->_cells[PqaMember::REPAID]['title'],
            $this->_cells[PqaMember::CATCH_PERCENTAGE]['title'],
        ];
        
        $this->_write($row);
    }
    
    /**
     * fourth row
     */
    private function _columnNumbers()
    {
        $row = [];
        for ($count = 1; $count <= PqaMember::COLUMNS; $count++) {
            $row[] = $count;
        }
        
        $this->_write($row);
    }
    
    private function _writeEntries()
    {
        $members = $this->_generator->members();
        $entries = $this->_generator->entries();
        
        $number = 0;
        foreach ($entries as $memberId => $entry) {
            ++$number;
            $member = $members[$memberId];
            $this->_row = $this->_default;
            $this->_row[PqaMember::A] = $number;
            $this->_row[PqaMember::B] = $member->name;
            $this->_row[PqaMember::C] = $member->code;
            $this->_row[PqaMember::D] = $this->view->lookup('fmc', $member->fmc_id);
            $this->_row[PqaMember::R] = $this->integer($entry['par']);
            $this->_row[PqaMember::S] = $this->integer($entry['catch']);
            $this->_row[PqaMember::U] = $this->integer($entry['percentage_catch']) . $this->percentage();
   
            foreach ($this->_direct as $key => $field) {
                $this->_row[$key] = $this->integer($entry[$field]);
            }
            
            $this->_write();
        }
    }
    
    private function _writeGrandTotal()
    {
        $grandTotal = $this->_generator->grandTotal();
        $this->_row = $this->_default;
        $this->_row[PqaMember::B] = 'Grand Total';
        $this->_row[PqaMember::E] = $grandTotal['loans'];
        $this->_row[PqaMember::F] = $grandTotal['loan_amount'];
        $this->_row[PqaMember::G] = $grandTotal['emi'];
        $this->_row[PqaMember::H] = $grandTotal['principal_prepaid_current'];
        $this->_row[PqaMember::I] = $grandTotal['principal_arrears'];
        $this->_row[PqaMember::J] = $grandTotal['principal'];
        $this->_row[PqaMember::K] = $grandTotal['interest'];
        $this->_row[PqaMember::L] = $grandTotal['total'];
        $this->_row[PqaMember::M] = $grandTotal['scheduled_outstanding'];
        $this->_row[PqaMember::N] = $grandTotal['principal_outstanding'];
        $this->_row[PqaMember::O] = $grandTotal['actual_outstanding'];
        $this->_row[PqaMember::P] = $grandTotal['overdue'];
        $this->_row[PqaMember::R] = $grandTotal['par'];
        $this->_row[PqaMember::S] = $grandTotal['catch'];
        $this->_row[PqaMember::T] = $grandTotal['repaid'];
        $this->_row[PqaMember::U] = $this->view->percentage($grandTotal['repaid'], $grandTotal['catch']) . $this->percentage();
        $this->_write();
    }
    
}
