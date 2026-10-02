<?php
/**
 * FishDaily Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class FishDaily extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::FISH_DAILY);
    }

}
