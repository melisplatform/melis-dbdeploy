<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service\Factory;

use MelisDbDeploy\Service\MelisDbDeployDiscoveryService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisDbDeployDiscoveryServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $moduleSvc = $sl->get('ModulesService');
        $composer  = $moduleSvc->getComposer();

        $service   = new MelisDbDeployDiscoveryService($composer);
        $service->setServiceLocator($sl);

        return $service;
    }
}