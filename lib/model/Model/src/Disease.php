<?php
/**
 * Medicine Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Disease extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DISEASE);
    }

}
