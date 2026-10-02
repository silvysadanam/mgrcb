<?php
/**
 * MemberCard Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class MemberCard extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::MEMBER_CARD);
    }

}
