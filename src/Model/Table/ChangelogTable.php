<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Model\Table;

use Laminas\Db\TableGateway\TableGateway;
use MelisEngine\Model\Tables\MelisGenericTable;

class ChangelogTable extends MelisGenericTable
{
    /**
     * Model table
     */
    const TABLE = 'changelog';

    /**
     * Table primary key
     */
    const PRIMARY_KEY = 'change_number';

    public function __construct()
    {
        $this->idField = self::PRIMARY_KEY;
    }
}