<?php
/**
 * MemberPhoto Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class BeneficiaryPhoto extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::BENEFICIARY_PHOTO);
    }

}
