<?php
/**
 * Backend Membership Create Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Membership
 */

namespace Backend\Form\Membership;

use Backend\Form\{DocumentForm, PhotoForm};

use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{MemberCard, Boat, MemberDocument, DocumentType, MemberPhoto, Account};

use Shared\Utility\{DocumentDirectory, FileTypeDetector, MembershipValidator};


class SearchForm extends Form
{
    /**
     * 
     * @var \Shared\Utility\MembershipValidator
     */
    private $_memberValidator;
    /**
     * 
     * @var int
     */
    private $_memberId;
    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'status', 'mtype', 'type', 'name', 'parish', 'location', 'mobile', 'show_all' ,'report_option'];
        $this->addFields($fields);
        $this->_memberValidator = new MembershipValidator();
    }
    
   
    public function isFilterValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();

        $showAll  = !empty($this->_formValues['show_all']);
        $status   = $this->_formValues['status'] ?? 0;
        $mtype    = $this->_formValues['mtype'] ?? 0;
        $type     = $this->_formValues['type'] ?? 0;
        $name     = trim($this->_formValues['name'] ?? '');
        $parish   = trim($this->_formValues['parish'] ?? '');
        $location = trim($this->_formValues['location'] ?? '');
        $mobile   = trim($this->_formValues['mobile'] ?? '');

        // At least one search criteria must be provided,
        // unless "Show All" is selected.
        if (
            !$showAll &&
            (int) $status === 0 &&
            (int) $mtype === 0 &&
            (int) $type === 0 &&
            $name === '' &&
            $parish === '' &&
            $location === '' &&
            $mobile === ''
        ) {
            $this->error->required('at least one search criteria');
        }

        // Validate mobile only if entered
        if ($mobile !== '' && ! $this->_memberValidator->mobile($mobile)) {
            $this->error->invalid('Mobile');
        }

        return $this->noError();
    }
}
