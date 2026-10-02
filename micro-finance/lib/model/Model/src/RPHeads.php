<?php
/**
 * DeductionType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class RPHeads extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::RP_HEADS);
    }

}
