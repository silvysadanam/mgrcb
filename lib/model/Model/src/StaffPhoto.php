<?php
/**
 * MemberPhoto Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class StaffPhoto extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::STAFF_PHOTO);
    }

}
