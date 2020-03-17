<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service\Factory;

use MelisDbDeploy\Service\MelisDbDeployDeployService;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\FactoryInterface;
class MelisDbDeployDeployServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $service   = new MelisDbDeployDeployService();
        $service->setServiceLocator($sl);

        return $service;
    }
}