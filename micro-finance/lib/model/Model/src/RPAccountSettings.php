<?php
/**
 * DeductionType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class RPAccountSettings extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::RP_ACCOUNT_SETTINGS);
    }

}
