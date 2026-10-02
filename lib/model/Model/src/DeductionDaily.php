<?php
/**
 * DeductionDaily Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DeductionDaily extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DEDUCTION_DAILY);
    }

}
