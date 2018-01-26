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
use MelisCore\Service\MelisCoreGeneralService;

class MelisDbDeployDiscoveryService extends MelisCoreGeneralService
{
    const VENDOR            = 'melisplatform';
    const CACHE_DELTAS_PATH = 'dbdeploy';

    public function __construct($composer)
    {
        $this->setComposer($composer);
    }

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param $composer
     */
    public function setComposer($composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Processing all Melis Platform Modules that need upgrade database
     * @param String  $module
     */
    public function processing($module = null)
    {
        /** @var MelisDbDeployDeployService $deployService */
        $deployService = $this->getServiceLocator()->get('MelisDbDeployDeployService');

        if (false === $deployService->isInstalled()) {
            $deployService->install();
        }

        $deltas = $this->copyDeltas($module);
        $deployService->applyDeltaPath(realpath('cache' . DIRECTORY_SEPARATOR . self::CACHE_DELTAS_PATH));
    }


    /**
     * Find melis delta migration that match
     * condition of extra dbdeploy
     */
    public function copyDeltas($module = null)
    {
        $composer  = $this->getComposer();

        $vendorDir = $composer->getConfig()->get('vendor-dir');

        $packages = $this->getLocalPackages();

        $deltas = [];


        foreach ($packages as $package) {
            $vendor = explode('/', $package->getName(), 2);

            if (empty($vendor) || static::VENDOR !== $vendor[0]) {
                continue;
            }

            $extra = $package->getExtra();

            if (!in_array('dbdeploy', $extra) || true !== $extra['dbdeploy']) {
                continue;
            }

            if(!is_null($module) && !empty($module)) {
                if(trim($extra['module-name']) === trim($module)) {
                    if (in_array('dbdeploy', $extra) && true === $extra['dbdeploy']) {
                        $deltas = static::copyDeltasFromPackage($package, $vendorDir);
                    }
                    break;
                }
            }
            else {
                $deltas = array_merge(
                    $deltas,
                    static::copyDeltasFromPackage($package, $vendorDir)
                );
            }

        }

        return $deltas;

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
        $deltaPath = 'cache' . $sp . self::CACHE_DELTAS_PATH . $sp;

        if (!file_exists($deltaPath)) {
            mkdir($deltaPath, 750);
        }

        $deltas = [];

        foreach ($files as $file) {
            copy($file, $deltaPath . basename($file));
            $deltas[]    = $deltaPath . basename($file);
        }

        if (empty($files)) {
            return [];
        }


        return $deltas;
    }
}
