<?php
/**
 * DocumentType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DocumentType extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DOCUMENT_TYPE);
    }

}
