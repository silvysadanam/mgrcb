<?php
/**
 * Member Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class BeneficiaryEquipment extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::BENEFICIARY_EQUIPMENT);
    }

}
