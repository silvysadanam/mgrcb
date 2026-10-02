<?php
/**
 * Application PqrLoanExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\CsvExport;
use Application\Generator\LoanReport;

use Shared\Column\PqrLoan;


class PqrLoanExport extends CsvExport
{
    /**
     * 
     * @var \Application\Utility\PqrGenerator
     */
    private $_generator;
    
    /**
     * 
     * @param \Web\View\ViewModel $view
     */
    public function __construct($view)
    {
        parent::__construct($view);
        
        $this->cells(new PqrLoan());
    }

    /**
     * run
     * 
     * @param \Application\Generator\LoanReport $generator
     */
    public function run(LoanReport $generator)
    {
        $this->_generator = $generator;
        //$heading = str_replace(' - ', '-',$this->_generator->heading());
        $title = PqrLoan::HEADING_MAIN;
        $this->loadWriter($title);
        $this->_titleRows();
        $this->_writeEntries();
        $this->_writer->finish();
    }
    
    /**
     * write title rows
     */
    private function _titleRows()
    {
        $this->_write([PqrLoan::HEADING_MAIN]);
        $filters = $this->_generator->filters();
        $this->_write([$filters['fmc'] . ' Level']);
        $this->_write(['Source: ' . $filters['type']]);
        $this->_write(['Type: ' . $filters['type']]);
        $this->_write(['Loan Amount Range: ' . $filters['amount']]);
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
            $this->_cells[PqrLoan::AGEING]['title'],
            $this->_cells[PqrLoan::LOANS]['title'],
            $this->_cells[PqrLoan::LOAN_AMOUNT]['title'],
            $this->_cells[PqrLoan::SCHEDULED_OUTSTANDING]['title'],
            $this->_cells[PqrLoan::PRINCIPAL_OUTSTANDING]['title'],
            $this->_cells[PqrLoan::ACTUAL_OUTSTANDING]['title'],
            $this->_cells[PqrLoan::OVERDUE]['title'],
            $this->_cells[PqrLoan::PAR]['title'],
            $this->_cells[PqrLoan::PERCENTAGE_LOANS]['title'],
            $this->_cells[PqrLoan::PERCENTAGE_PORTFOLIO]['title'],
            $this->_cells[PqrLoan::LLR_PERCENTAGE]['title'],
            $this->_cells[PqrLoan::LLR_AMOUNT]['title'],
        ];
        
        $this->_write($row);
    }
    
    /**
     * column numbers
     */
    private function _columnNumbers()
    {
        $row = [];
        for ($count = 1; $count <= PqrLoan::COLUMNS; $count++) {
            $row[] = $count;
        }
        
        $this->_write($row);
    }
    
    private function _writeEntries()
    {
        $entries = $this->_generator->entries();
        $total = $this->_generator->total();
        $ageing = $this->view->lookupStore('ageing_title');
        $count = 0;
        foreach ($entries as $ageingIndex => $entry) {
            ++$count;
            $this->_row = $this->_default;
            $this->_row[PqrLoan::A] = $count;
            $this->_row[PqrLoan::B] = $ageing[$ageingIndex];
            $this->_row[PqrLoan::C] = $entry['loans'];
            $this->_row[PqrLoan::D] = $this->view->lakhs($entry['amount']);
            $this->_row[PqrLoan::E] = $this->view->lakhs($entry['scheduled_outstanding']);
            $this->_row[PqrLoan::F] = $this->view->lakhs($entry['principal_outstanding']);
            $this->_row[PqrLoan::G] = $this->view->lakhs($entry['actual_outstanding']);
            $this->_row[PqrLoan::H] = $this->view->lakhs($entry['overdue']);
            $this->_row[PqrLoan::I] = $this->view->lakhs($entry['par']);
            $this->_row[PqrLoan::J] = $this->view->integer($entry['percentage_loans']) . '%';
            $this->_row[PqrLoan::K] = $this->view->integer($entry['percentage_portfolio']) . '%';
            $this->_row[PqrLoan::L] = $this->view->integer($entry['llr_percentage']) . '%';
            $this->_row[PqrLoan::M] = $this->view->lakhs($entry['llr_amount']);
            
            $this->_write();
        }
        
        $this->_row = $this->_default;
        $this->_row[PqrLoan::C] = $total['loans'];
        $this->_row[PqrLoan::D] = $this->view->lakhs($total['amount']);
        $this->_row[PqrLoan::E] = $this->view->lakhs($total['scheduled_outstanding']);
        $this->_row[PqrLoan::F] = $this->view->lakhs($total['principal_outstanding']);
        $this->_row[PqrLoan::G] = $this->view->lakhs($total['actual_outstanding']);
        $this->_row[PqrLoan::H] = $this->view->lakhs($total['overdue']);
        $this->_row[PqrLoan::I] = $this->view->lakhs($total['par']);
        $this->_row[PqrLoan::J] = $this->view->integer($total['percentage_loans']) . '%';
        $this->_row[PqrLoan::K] = $this->view->integer($total['percentage_portfolio']) . '%';
        $this->_row[PqrLoan::M] = $this->view->lakhs($total['llr_amount']);
        $this->_write();
    }
    
}
