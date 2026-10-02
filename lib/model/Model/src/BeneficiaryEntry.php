<?php
/**
 * BenificiaryEntry Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class BeneficiaryEntry extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::BENEFICIARY_ENTRY);
    }

}
