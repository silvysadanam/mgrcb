<?php
/**
 * Application Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Utility\LoanHelper;
use Model\{Member, Loan, Pqam, Fmc, LoanSource, LoanType};


use Web\Utility\DateHelper;


class Generator
{
    const LEVEL_LOAN = 0;
    const LEVEL_MEMEBR = 1;
    
    const FIELD_FMC = 'fmc';
    const FIELD_CODE = 'code';
    const FIELD_SOURCE = 'source';
    const FIELD_TYPE = 'type';
    const FIELD_AMOUNT = 'amount';
    const FIELD_DATE = 'date';
    const FIELD_AGEING = 'ageing';
    
    const TYPE_ANALYSIS = 1;
    const TYPE_REPORT = 2;
    
    const ALL = 'ALL';
    const FILTER_GLUE = ' + ';
    
    /**
     * 
     * @var \Application\Utility\Branch
     */
    protected $_branch;
    
    /**
     * 
     * @var \Model\Loan
     */
    protected $_model;
    
     /**
     * 
     * @var \Model\Fmc
     */
    protected $_fmcModel;
    
    /**
     * 
     * @var \Model\Pqam
     */
    protected $_pqamModel;
    
    /**
     * 
     * @var \Model\Member
     */
    protected $_memberModel;
    
    /**
     * 
     * @var \Model\LoanSource
     */
    protected $_sourceModel;
    
     /**
     * 
     * @var \Model\LoanType
     */
    protected $_typeModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    protected $_sql;
    
    /**
     * 
     * @var string
     */
    protected $_reportingDate;
    
    /**
     * 
     * @var array
     */
    protected $_values = [];

    /**
     * 
     * @var array
     */
    protected $_members = [];
    
     /**
     * 
     * @var array
     */
    protected $_loans = [];
    
    /**
     * 
     * @var array
     */
    protected $_entries = [];
    
    /**
     * 
     * @var int
     */
    protected $_fmcId = 0;
    
    /**
     * 
     * @var int
     */
    protected $_level = 0;
    
    /**
     * 
     * @var array
     */
    protected $_filters = [];
    
    /**
     * 
     * @var string
     */
    protected $_asOn;
    
    /**
     * 
     * @var array
     */
    protected $_memberTotal = [];
    
    /**
     * 
     * @var array
     */
    protected $_total = [];
    
    /**
     * 
     * @var array
     */
    protected $_fields = [];
    
    /**
     * 
     * @var array
     */
    protected $_arrayFields = [];
    
    /**
     * 
     * @var int
     */
    protected $_generatorType = 1;
    
    /**
     * 
     * @var int
     */
    protected $_memberId = 0;
    
    /**
     * 
     * @var string
     */
    protected $_where = false;
    
    /**
     * 
     * @var int
     */
    protected $_recordCount = 0;
    
    /**
     * 
     * @var array
     */
    protected $_defaults = [];
    
    /**
     * 
     * @var array
     */
    protected $_memberIds = [];
    
    /**
     * 
     * @var array
     */
    protected $_grandTotal = [];
    
    /**
     * 
     * @var array
     */
    protected $_memberCatch = [];
    
    /**
     * 
     * @var array
     */
    private $_fieldMap;
    
    /**
     * 
     * @var string
     */
    private $_downloadUrl;
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_sql = $this->_model->getSql();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_pqamModel = new Pqam();
        $this->_sourceModel = new LoanSource();
        $this->_typeModel = new LoanType();
        $this->_reportingDate = LoanHelper::reportingDate();
        
        $this->_fieldMap = [
            'fmc_id' => self::FIELD_FMC,
            'loan_source_id' => self::FIELD_SOURCE,
            'loan_type_id' => self::FIELD_TYPE,
            'reporting_date' => self::FIELD_DATE,
            'amount' => self::FIELD_AMOUNT,
            'member_code' => self::FIELD_CODE,
            'ageing' => self::FIELD_AGEING,
        ];
        
        $this->_fields = [self::FIELD_FMC, self::FIELD_DATE];
        $this->_arrayFields = [self::FIELD_SOURCE, self::FIELD_TYPE, self::FIELD_AMOUNT];
    }
    
    /**
     * set branch
     * 
     * @param \Application\Utility\Branch $branch
     */
    public function branch($branch)
    {
        $this->_branch = $branch;
    }
    
    /**
     * set fields
     * 
     * @param array $fields
     */
    public function fields($fields)
    {
        foreach ($fields as $field) {
            if (! in_array($field, $this->_fields)) {
                $this->_fields[] = $field;
            }
        }
    }
    
    /**
     * set values
     * 
     * @param array $values
     */
    public function values($values)
    {
        foreach ($values as $field => $value) {
            if (isset($this->_fieldMap[$field])) {
                $mappedField = $this->_fieldMap[$field];
                if (in_array($mappedField, $this->_fields)) {
                    $this->_values[$mappedField] = $value;
                }
            }
        }
        
        $this->_setReportingDate();
        $this->_loadValues();
    }
    
    /**
     * set level
     * 
     * @return $this
     */
    public function setLevel($level)
    {
        $this->_level = $level;
        
        return $this;
    }
    
    /**
     * get level
     * 
     * @return int
     */
    public function level()
    {
        return $this->_level;
    }
    
    /**
     * get members
     * 
     * @return array
     */
    public function members()
    {
        return $this->_members;
    }
    
     /**
     * get entries
     * 
     * @return array
     */
    public function entries()
    {
        return $this->_entries;
    }
    
     /**
     * get loans
     * 
     * @return array
     */
    public function loans()
    {
        return $this->_loans;
    }
    
    /**
     * member catch
     * 
     * @return string
     */
    public function memberCatch()
    {
        return $this->_memberCatch;
    }
    
    /**
     * member total
     * 
     * @return array
     */
    public function memberTotal()
    {
        return $this->_memberTotal;
    }
    
    /**
     * reporting date
     * 
     * @return string
     */
    public function reportingDate()
    {
        return $this->_reportingDate;
    }
    
    /**
     * as on date
     * 
     * @return string
     */
    public function asOn()
    {
        return $this->_asOn;
    }
    
    /**
     * get filters
     * 
     * @return array
     */
    public function filters()
    {
        return $this->_filters;
    }
    
    /**
     * check entries
     * 
     * @return boolean
     */
    public function hasEntries()
    {
        return ! empty($this->_entries);
    }
    
    /**
     * set loans level
     */
    public function loansLevel()
    {
        $this->_level = self::LEVEL_LOAN;
    }
    
    /**
     * set members level
     */
    public function membersLevel()
    {
        $this->_level = self::LEVEL_MEMEBR;
    }
    
    /**
     * get total
     * 
     * @return array
     */
    public function total()
    {
        return $this->_total;
    }
    
    /**
     * get record count
     * 
     * @return int
     */
    public function records()
    {
        return $this->_recordCount;
    }
    
    /**
     * grand total
     * 
     * @return array
     */
    public function grandTotal()
    {
        return $this->_grandTotal;
    }
    
    public function setDownloadUrl($url)
    {
        $this->_downloadUrl = $url;
    }
    
    public function getDownloadButton()
    {
        return $this->_downloadUrl . '/?' . http_build_query($_GET);
    }
    
    
    /**
     * check member level
     * 
     * @return boolean
     */
    protected function _isMembersLevel()
    {
        return self::LEVEL_MEMEBR == $this->_level;
    }
    
    /**
     * check loan level
     * 
     * @return boolean
     */
    protected function _isLoansLevel()
    {
        return self::LEVEL_LOAN == $this->_level;
    }
        
    /**
     * calculate percentage
     * 
     * @param float $value
     * @param float $total
     * @return int
     */
    protected function _percentage($value, $total)
    {
        if ($value > 0 && $total > 0) {
            return  ceil(100 * ($value / $total));
        }
        
        return 0;
    }
    
    /**
     * calculate ageing and par
     * 
     * @param float $overdue
     * @param float $outstanding
     * @param int $emi
     * @return array
     */
    protected function _ageingAndPar($overdue, $outstanding, $emi)
    {
        $result = ['ageing' => 0, 'par' => 0];
        if ($overdue > 0) {
            $result['ageing'] = ceil(($overdue / $emi) * 30);
            $result['par'] = $outstanding;
        }
        
        return $result;
    }
    
    /**
     * calculate overdue, par and ageing
     * 
     * @param float $scheduled
     * @param float $actual
     * @param int $emi
     * @return array
     */
    protected function _overdue($scheduled, $actual, $emi = 0)
    {
        $result = ['overdue' => 0, 'par' => 0, 'ageing' => 0];
        if ($actual > $scheduled) {
            $result['overdue'] = $actual - $scheduled;
            $result['par'] = $actual;
            if ($emi > 0) {
                $result['ageing'] = ceil(($result['overdue'] / $emi) * 30);
            }
        } else {
            $result['overdue'] = $actual - $scheduled;
        }
        
        return $result;
    }
    
    
    /**
     * set reporting date
     */
    private function _setReportingDate()
    {
        if (isset($this->_values[self::FIELD_DATE])) {
            if ('' != $this->_values[self::FIELD_DATE]) {
                $dateTime = DateHelper::fromFormat($this->_values[self::FIELD_DATE], LoanHelper::REPORTING_DATE_ID);
                if (false !== $dateTime) {
                    $days = $dateTime->format('t');
                    $month = $dateTime->format('n');
                    $year = $dateTime->format('Y');
                    $dateTime->setDate($year, $month, $days);
                    $this->_reportingDate = $dateTime->format(DateHelper::MYSQL_DATE);
                }
            }
        }
        
        $this->_asOn = DateHelper::format('j F Y', $this->_reportingDate);
        $this->_filters[self::FIELD_DATE] = $this->_asOn;
    }
    
    /**
     * load and filter values
     */
    private function _loadValues()
    {
        $this->_loadFmcFilter();
        $this->_loadMemberFilter();
        $this->_loadSourceFilter();
        $this->_loadTypeFilter();
        $this->_loadAmountFilter();
        $this->_loadAgeingFilter();
    }
    
    /**
     * fmc filter
     */
    private function _loadFmcFilter()
    {
        if (null !== $this->_branch) {
            $this->_fmcId = $this->_branch->id();
            $this->_values[self::FIELD_FMC] = $this->_fmcId;
        } elseif ($this->_values[self::FIELD_FMC] > 0) {
            if ($this->_fmcModel->hasId($this->_values[self::FIELD_FMC])) {
                $this->_fmcId = $this->_values[self::FIELD_FMC];
                $this->_values[self::FIELD_FMC] = $this->_fmcId;
            } else {
                $this->_values[self::FIELD_FMC] = 0;
            }
        }
        
        if ($this->_fmcId > 0) {
            $this->_filters[self::FIELD_FMC] = 'FMC ' . $this->_fmcModel->selectColumnById('name', $this->_fmcId);
            $this->_memberIds = $this->_memberModel->selectIds(['fmc_id' => $this->_fmcId]);
        } else {
            $this->_filters[self::FIELD_FMC] = 'FWS';
        }
    }
    
    /**
     * load member filter
     * 
     * @return null
     */
    private function _loadMemberFilter()
    {
        if (! in_array(self::FIELD_CODE, $this->_fields)) {
            return;
        }
        
        if ('' != $this->_values[self::FIELD_CODE]) {
            if ($this->_memberModel->checkByColumn('code', $this->_values[self::FIELD_CODE])) {
                $memberId = $this->_memberModel->selectIdByColumn('code', $this->_values[self::FIELD_CODE]);
                if ($this->_fmcId > 0) {
                    if ($this->_fmcId == $this->_memberModel->selectColumnById('fmc_id', $memberId)) {
                        $this->_memberId = $memberId;
                    }
                } else {
                    $this->_memberId = $memberId;
                }
            }
        }
    }
    
    /**
     * load source filter
     * 
     * @return null
     */
    private function _loadSourceFilter()
    {
        if (! in_array(self::FIELD_SOURCE, $this->_fields)) {
            return;
        }
        
        if (empty($this->_values[self::FIELD_SOURCE])) {
            $this->_filters[self::FIELD_SOURCE] = self::ALL;
            return;
        }
        
        $sources = [];
        foreach ($this->_values[self::FIELD_SOURCE] as $sourceId) {
            if ($this->_isInteger($sourceId)) {
                if (! in_array($sourceId, $sources)) {
                    if ($this->_sourceModel->hasId($sourceId)) {
                        $sources[] = $sourceId;
                    }
                }
            }
        }
        
        $this->_values[self::FIELD_SOURCE] = $sources;
        if (empty($sources)) {
            $this->_filters[self::FIELD_SOURCE] = self::ALL;
            return;
        }
        
        $filters = [];
        foreach ($sources as $id) {
            $filters[] = $this->_sourceModel->selectColumnById('loan_source', $id);
        }
        $this->_filters[self::FIELD_SOURCE] = implode(self::FILTER_GLUE, $filters);
    }
    
    /**
     * load type filter
     * 
     * @return null
     */
    private function _loadTypeFilter()
    {
        if (! in_array(self::FIELD_TYPE, $this->_fields)) {
            return;
        }
        
        if (empty($this->_values[self::FIELD_TYPE])) {
            $this->_filters[self::FIELD_TYPE] = self::ALL;
            return;
        }
        
        $types = [];
        foreach ($this->_values[self::FIELD_TYPE] as $typeId) {
            if ($this->_isInteger($typeId)) {
                if (! in_array($typeId, $types)) {
                    if ($this->_typeModel->hasId($typeId)) {
                        $types[] = $typeId;
                    }
                }
            }
        }
        
        $this->_values[self::FIELD_TYPE] = $types;
        if (empty($types)) {
            $this->_filters[self::FIELD_TYPE] = self::ALL;
            return;
        }
        
        $filters = [];
        foreach ($types as $id) {
            $filters[] = $this->_typeModel->selectColumnById('loan_type', $id);
        }
        $this->_filters[self::FIELD_TYPE] = implode(self::FILTER_GLUE, $filters);
    }
    
    /**
     * load amount filter
     * 
     * @return null
     */
    private function _loadAmountFilter()
    {
        if (! in_array(self::FIELD_AMOUNT, $this->_fields)) {
            return;
        }
        
        if (empty($this->_values[self::FIELD_AMOUNT])) {
            $this->_filters[self::FIELD_AMOUNT] = self::ALL;
            return;
        }
        
        if ($this->_isMembersLevel()) {
            $options = LoanHelper::totalLoanAmountOptions();
        } else {
            $options = LoanHelper::loanAmountOptions();
        }
        
        $amounts = [];
        foreach ($this->_values[self::FIELD_AMOUNT] as $item) {
            if ($this->_isInteger($item)) {
                if (! in_array($item, $amounts)) {
                    if (isset($options[$item])) {
                        $amounts[] = $item;
                    }
                }
            }
        }
        
        $this->_values[self::FIELD_AMOUNT] = $amounts;
        if (empty($amounts)) {
            $this->_filters[self::FIELD_AMOUNT] = self::ALL;
            return;
        }
        
        $filters = [];
        foreach ($amounts as $id) {
            $filters[] = $options[$id];
        }
        $this->_filters[self::FIELD_AMOUNT] = implode(self::FILTER_GLUE, $filters);
    }
    
    /**
     * load ageing filter
     * 
     * @return null
     */
    private function _loadAgeingFilter()
    {
        if (! in_array(self::FIELD_AGEING, $this->_fields)) {
            return;
        }
        
        if (0 == $this->_values[self::FIELD_AGEING] || $this->_values[self::FIELD_AGEING] > LoanHelper::AGEING_MAX_ID) {
            $this->_filters[self::FIELD_AGEING] = self::ALL;
            $this->_values[self::FIELD_AGEING] = 0;
            return;
        }
        
        $ageingOptions = LoanHelper::ageingList();
        $this->_filters[self::FIELD_AGEING] = $ageingOptions[$this->_values[self::FIELD_AGEING]];
    }
    
    /**
     * check for integer value greater than 0
     * 
     * @param int $number
     * @return boolean
     */
    private function _isInteger($number)
    {
        if (0 == $number) {
            return false;
        }
        
        return 1 == preg_match('/^[1-9]\d*$/', $number);
    }
    
}
