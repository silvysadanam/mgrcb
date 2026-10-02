<?php
/**
 * MemberPhoto Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class MemberPhoto extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::MEMBER_PHOTO);
    }

}
