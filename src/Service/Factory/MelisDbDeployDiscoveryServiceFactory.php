<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service\Factory;

use Interop\Container\ContainerInterface;

class MelisDbDeployDiscoveryServiceFactory
{
    public function __invoke(ContainerInterface $container, $requestedName)
    {
        $moduleSvc = $container->get('MelisAssetManagerModulesService');
        $composer  = $moduleSvc->getComposer();

        $instance = new $requestedName($composer);
        $instance->setServiceManager($container);
        return $instance;
    }
}