<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Laminas\ServiceManager\ServiceManager;
use MelisCore\Service\MelisServiceManager;

class MelisDbDeployDiscoveryService extends MelisServiceManager
{
    const VENDOR = 'melisplatform';
    const CACHE_DELTAS_PATH = 'data';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param ServiceManager $service
     */
    public function setServiceManager(ServiceManager $service)
    {
        $this->serviceManager = $service;

        $moduleSvc = $service->get('MelisAssetManagerModulesService');
        $composer  = $moduleSvc->getComposer();
        $this->setComposer($composer);
    }

    /**
     * Processing all Melis Platform Modules that need upgrade database
     *
     * @param String $module
     */
    public function processing($module = null)
    {

        // create dbdeploy folder if not existing
        // SECURITY (audit DB-H1): never make the delta cache world-writable — every
        // *.sql it contains is executed against the database with the platform's DB
        // account, so a 0777 directory hands arbitrary SQL execution to any local
        // user. 0755 is enough: the PHP user owns the directory it creates; on an
        // inherited unreadable directory chmod only succeeds for its owner anyway,
        // and the copy below then fails loudly instead of opening the door.
        $dbDeployPath = $_SERVER['DOCUMENT_ROOT'] . '/../dbdeploy';
        if (!file_exists($dbDeployPath)) {
            mkdir($dbDeployPath, 0755);
        }
        if (!is_writable($dbDeployPath) || !is_readable($dbDeployPath)) {
            @chmod($dbDeployPath, 0755);
        }


        /** @var MelisDbDeployDeployService $deployService */
        $deployService = $this->getServiceManager()->get('MelisDbDeployDeployService');

        if (false === $deployService->isInstalled()) {
            $deployService->install();
        }

        $this->copyDeltas($module);
        $deployService->applyDeltaPath(realpath('dbdeploy' . DIRECTORY_SEPARATOR . self::CACHE_DELTAS_PATH));
    }

    /**
     * Find melis delta migration that match
     * condition of extra dbdeploy
     */
    public function copyDeltas($module = null)
    {
        $composer = $this->getComposer();

        $vendorDir = $composer->getConfig()->get('vendor-dir');

        $packages = $this->getLocalPackages();

        $deltas = [];

        foreach ($packages as $package) {
            $vendor = explode('/', $package->getName(), 2);

            if (empty($vendor) || static::VENDOR !== $vendor[0]) {
                continue;
            }

            $extra = $package->getExtra();

            if (!is_null($module) && !empty($module)) {
                if (isset($extra['module-name']) && trim($extra['module-name']) === trim($module)) {
                    $deltas = static::copyDeltasFromPackage($package, $vendorDir);
                    break;
                }
            } else {
                $deltas = array_merge(
                    $deltas,
                    static::copyDeltasFromPackage($package, $vendorDir)
                );
            }

        }

        return $deltas;

    }

    /**
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * @param $composer
     */
    public function setComposer($composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return \Composer\Package\PackageInterface[]
     */
    protected function getLocalPackages()
    {
        return $this->getComposer()
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getCanonicalPackages();
    }

    /**
     * @param PackageInterface $package
     * @param $vendorDir
     *
     * @return array
     */
    protected static function copyDeltasFromPackage(PackageInterface $package, $vendorDir)
    {
        $sp = DIRECTORY_SEPARATOR;
        $path = $vendorDir . $sp . $package->getName() . $sp . 'install/dbdeploy';


        if (false === file_exists($path)) {
            return [];
        }

        $files = glob("$path/*.sql");
        $deltaPath = 'dbdeploy' . $sp . self::CACHE_DELTAS_PATH . $sp;

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/../' . $deltaPath)) {
            // SECURITY (audit DB-H1): 0755, never 0777 — see processing().
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/../' . $deltaPath, 0755);
        }


        $deltas = [];

        foreach ($files as $file) {
            copy($file, $deltaPath . basename($file));
            $deltas[] = $deltaPath . basename($file);
        }

        if (empty($files)) {
            return [];
        }

        return $deltas;
    }
}
