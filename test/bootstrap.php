<?php

namespace MelisDbDeployTest;

use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

class Bootstrap
{
    private static $serviceManager;
    private static $config;

    public static function init()
    {
        $config = include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'module.config.php';
        $serviceManager = new ServiceManager(new ServiceManagerConfig($config['service_manager']));

        static::$serviceManager = $serviceManager;
        static::$config = $config;
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    public static function getConfig()
    {
        return static::$config;
    }
}

Bootstrap::init();
