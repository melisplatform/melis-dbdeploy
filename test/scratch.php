<?php

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

$composer = new \Composer\Composer();

$smConfig = include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'module.config.php';
$serviceManager = new \Zend\ServiceManager\ServiceManager(new \Zend\Mvc\Service\ServiceManagerConfig($smConfig['service_manager']));

/** @var \MelisDbDeploy\Service\MelisDbDeployDiscoveryService $discovery */
$discovery = $serviceManager->get('MelisDbDeployDiscoveryService');
//$discovery->processing($composer);


$project = new Project();
//$project->set
$DbDeployTask = new DbDeployTask();

$DbDeployTask->setProject($project);
$DbDeployTask->setDir('/var/www/html/vendor/melisplatform/melis-cms-news/install/');
$DbDeployTask->setUrl('mysql:host=mysql;dbname=melis');
$DbDeployTask->setUserId('root');
$DbDeployTask->setPassword('rootpasswd');
$DbDeployTask->setOutputFile('melisplatform-dbdeploy.sql');
$DbDeployTask->setUndoOutputFile('melisplatform-dbdeploy-reverse.sql');

$DbDeployTask->main();