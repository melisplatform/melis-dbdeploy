<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

use MelisDbDeploy\Model\Table\ChangelogTable;
use MelisDbDeploy\Service\Factory\MelisDbDeployDiscoveryServiceFactory;
use MelisDbDeploy\Service\MelisDbDeployDeployService;
use MelisDbDeploy\Service\MelisDbDeployDiscoveryService;
use MelisDbDeploy\Model\Table\Factory\ChangelogTableFactory;
use MelisCore\Model\Tables\Factory\AbstractTableGatewayFactory;

return [
    'service_manager' => [
        'aliases' => [
            // Services
            'MelisDbDeployDeployService'            => MelisDbDeployDeployService::class,
            'MelisDbDeployDiscoveryService'         => MelisDbDeployDiscoveryService::class,
            // Model
            'ChangelogTable'                        => ChangelogTable::class,
        ],
    ],
];