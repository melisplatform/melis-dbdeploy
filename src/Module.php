<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy;

use MelisDbDeploy\Service\MelisDbDeployDiscoveryService;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;


class Module
{
    public function init(ModuleManager $mm)
    {
    }

    public function getConfig()
    {
        $config = array();
        $configFiles = array(
            include __DIR__ . '/../config/module.config.php',
        );

        foreach ($configFiles as $file) {
            $config = ArrayUtils::merge($config, $file);
        }

        return $config;
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Trigger the processing of discovery patch and deploy sql migration
     * @param Event $event
     */
    public static function run()
    {
        $smConfig = include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'module.config.php';
        $serviceManager = new ServiceManager(new ServiceManagerConfig($smConfig['service_manager']));

        /** @var MelisDbDeployDiscoveryService $discovery */
        try {
          $discovery = $serviceManager->get('MelisDbDeployDiscoveryService');
          $discovery->processing();
        } catch (ConfigFileNotFoundException $exception) {
          // If missing config file, due nothing
          return;
        }
    }
}
