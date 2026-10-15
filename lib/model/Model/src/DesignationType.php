<?php
/**
 * CardType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DesignationType extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DESIGNATION_TYPE);
    }

}
