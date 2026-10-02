<?php
/**
 * Application PqrMemberExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\CsvExport;
use Application\Generator\MemberReport;

use Shared\Column\PqrMember;


class PqrMemberExport extends CsvExport
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
        
        $this->cells(new PqrMember());
    }

    /**
     * run
     * 
     * @param \Application\Generator\MemberReport $generator
     */
    public function run(MemberReport $generator)
    {
        $this->_generator = $generator;
        //$heading = str_replace(' - ', '-',$this->_generator->heading());
        $title = PqrMember::HEADING_MAIN;
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
        $this->_write([PqrMember::HEADING_MAIN]);
        $filters = $this->_generator->filters();
        $this->_write([$filters['fmc'] . ' Level']);
        $this->_write(['Total Loan Per Member Range: ' . $filters['amount']]);
        $this->_write(['As On: ' . $filters['date']]);
        $this->_columnTitles();
        $this->_columnNumbers();
    }
    
    /**
     * fourth row
     */
    private function _columnTitles()
    {
        $row = [
            $this->blank(),
            $this->_cells[PqrMember::AGEING]['title'],
            $this->_cells[PqrMember::MEMBERS]['title'],
            $this->_cells[PqrMember::LOAN_AMOUNT]['title'],
            $this->_cells[PqrMember::LOANS]['title'],
            $this->_cells[PqrMember::SCHEDULED_OUTSTANDING]['title'],
            $this->_cells[PqrMember::ACTUAL_OUTSTANDING]['title'],
            $this->_cells[PqrMember::OVERDUE]['title'],
            $this->_cells[PqrMember::PAR]['title'],
            $this->_cells[PqrMember::PERCENTAGE_MEMBERS]['title'],
            $this->_cells[PqrMember::PERCENTAGE_PORTFOLIO]['title'],
            $this->_cells[PqrMember::PERCENTAGE_LOANS]['title'],
            $this->_cells[PqrMember::LLR_PERCENTAGE]['title'],
            $this->_cells[PqrMember::LLR_AMOUNT]['title'],
        ];
        
        $this->_write($row);
    }
    
    /**
     * column numbers
     */
    private function _columnNumbers()
    {
        $row = [];
        for ($count = 1; $count <= PqrMember::COLUMNS; $count++) {
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
            $this->_row[PqrMember::A] = $count;
            $this->_row[PqrMember::B] = $ageing[$ageingIndex];
            $this->_row[PqrMember::C] = $entry['members'];
            $this->_row[PqrMember::D] = $this->view->lakhs($entry['amount']);
            $this->_row[PqrMember::E] = $entry['loans'];
            $this->_row[PqrMember::F] = $this->view->lakhs($entry['scheduled_outstanding']);
            $this->_row[PqrMember::G] = $this->view->lakhs($entry['principal_outstanding']);
            $this->_row[PqrMember::H] = $this->view->lakhs($entry['actual_outstanding']);
            $this->_row[PqrMember::I] = $this->view->lakhs($entry['overdue']);
            $this->_row[PqrMember::J] = $this->view->lakhs($entry['par']);
            $this->_row[PqrMember::K] = $this->view->integer($entry['percentage_members']) . '%';
            $this->_row[PqrMember::L] = $this->view->integer($entry['percentage_portfolio']) . '%';
            $this->_row[PqrMember::M] = $this->view->integer($entry['percentage_loans']) . '%';
            $this->_row[PqrMember::N] = $this->view->integer($entry['llr_percentage']) . '%';
            $this->_row[PqrMember::O] = $this->view->lakhs($entry['llr_amount']);
            
            $this->_write();
        }
        
        $this->_row = $this->_default;
        
        $this->_row[PqrMember::C] = $total['members'];
        $this->_row[PqrMember::D] = $this->view->lakhs($total['amount']);
        $this->_row[PqrMember::E] = $total['loans'];
        $this->_row[PqrMember::F] = $this->view->lakhs($total['scheduled_outstanding']);
        $this->_row[PqrMember::G] = $this->view->lakhs($total['principal_outstanding']);
        $this->_row[PqrMember::H] = $this->view->lakhs($total['actual_outstanding']);
        $this->_row[PqrMember::I] = $this->view->lakhs($total['overdue']);
        $this->_row[PqrMember::J] = $this->view->lakhs($total['par']);
        $this->_row[PqrMember::K] = $this->view->integer($total['percentage_members']) . '%';
        $this->_row[PqrMember::L] = $this->view->integer($total['percentage_portfolio']) . '%';
        $this->_row[PqrMember::M] = $this->view->integer($total['percentage_loans']) . '%';
        $this->_row[PqrMember::O] = $this->view->lakhs($total['llr_amount']);
        $this->_write();
    }
    
}
