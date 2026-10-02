<?php
/**
 * LoanSummary Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class LoanSummary extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::LOAN_SUMMARY);
    }

}
