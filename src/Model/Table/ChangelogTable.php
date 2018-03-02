<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Model\Table;

use Zend\Db\TableGateway\TableGateway;
use MelisDbDeploy\Model\Table\MelisGenericTable;
class ChangelogTable extends MelisGenericTable
{
    public function __construct(TableGateway $tableGateway)
    {
        parent::__construct($tableGateway);
        $this->idField = 'change_number';
    }
}