<?php
/**
 * PanchayathBrand Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class PanchayathBrand extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::PANCHAYATH_BRAND);
    }

}
