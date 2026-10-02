<?php
/**
 * NotificationComment Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class NotificationComment extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::NOTIFICATION_COMMENT);
    }

}
