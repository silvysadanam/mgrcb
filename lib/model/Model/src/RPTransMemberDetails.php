<?php
/**
 * AuctionChit Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class RPTransMemberDetails extends TableGateway
{
    protected $_primary = 'childID';
    public function __construct()
    {
        parent::__construct(Db::RP_TRANS_MEMBER_DETAILS, $this->_primary);
    }

}
