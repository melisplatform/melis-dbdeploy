<?php
/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */
namespace MelisDbDeploy;
use MelisDbDeploy\Service\MelisDbDeployDiscoveryService;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
class Module
{
    public function getConfig()
    {
        $config = [];
        $configFiles = [
            include __DIR__ . '/../config/module.config.php',
            include __DIR__ . '/../config/diagnostic.config.php',
        ];

        foreach ($configFiles as $file) {
            $config = ArrayUtils::merge($config, $file);
        }

        return $config;
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
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