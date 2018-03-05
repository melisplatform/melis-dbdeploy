<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

return [
    'service_manager' => [
        'aliases' => [
            'ChangelogTable' => 'MelisDbDeploy\Model\Table\ChangelogTable'
        ],
        'factories' => [
            'MelisDbDeployDiscoveryService' => \MelisDbDeploy\Service\Factory\MelisDbDeployDiscoveryServiceFactory::class,
            'MelisDbDeployDeployService' => MelisDbDeploy\Service\Factory\MelisDbDeployDeployServiceFactory::class,

            'MelisDbDeploy\Model\Table\ChangelogTable' => MelisDbDeploy\Model\Table\Factory\ChangelogTableFactory::class,
        ],
    ],
];