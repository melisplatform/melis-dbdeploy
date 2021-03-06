<?php

namespace MelisDbDeploy;

use Composer\Script\Event;
use MelisDbDeploy\Service\MelisDbDeployDeployService;

class DbDeployOnComposerUpdate
{
    /**
     * @var CACHE_DELTAS_PATH - Path where dbdeploy sql files to be stored
     */
    const CACHE_DELTAS_PATH = 'data';

    /**
     * @var int
     */
    private static $count = 1;

    public static function postUpdate(Event $event)
    {
        // Melis packages
        $melisComposer = new \MelisComposerDeploy\MelisComposer();
        $melisPackages = $melisComposer->getMelisPackages();

        array_map(function($package) {
            $path = pathinfo($package->name, PATHINFO_FILENAME);
            self::copyDeltasFromPackage($path);
        }, $melisPackages);

        print "\r\n";
        print 'Executing DB Deploy' . PHP_EOL;

        try {
            if (self::execDbDeploy()) {
                print 'Done!' . PHP_EOL;
            }
        } catch (\Exception $e) {
            print $e->getMessage();
        }

    }

    /**
     * Returns the full path of the current directory (used only in command-line)
     * @return mixed|null|string
     */
    private static function docRoot()
    {
        $docRoot = dirname(__DIR__);
        $parts = explode(DIRECTORY_SEPARATOR, $docRoot);
        $path = null;
        foreach ($parts as $idx => $part) {
            $path .= $part . DIRECTORY_SEPARATOR;
            if ($part == 'vendor') {
                break;
            }
        }
        $path = str_replace(DIRECTORY_SEPARATOR . 'vendor', '', $path);

        return $path;
    }

    /**
     * Copy the dbdeploy sql files inside self::CACHE_DELTAS_PATH
     *
     * @param $module
     *
     * @return null|void
     */
    private static function copyDeltasFromPackage($module)
    {
        $docRoot = self::docRoot();
        $melisVendorPath = $docRoot . 'vendor/melisplatform/' . $module;
        $dbDeployPath = $docRoot . 'dbdeploy/data/';
        if (!file_exists($melisVendorPath)) {
            return;
        }
        if (!file_exists($dbDeployPath)) {
            mkdir($dbDeployPath, 0777, true);
        }
        // copy dbdeploy file
        $packageDbdeployFiles = $melisVendorPath . '/install/dbdeploy/';
        if (!file_exists($packageDbdeployFiles)) {
            return null;
        }
        $packageDbdeployFiles = glob($packageDbdeployFiles . '*.sql');
        $moduleName = self::toModuleName($module);

        print  "* $moduleName\r\n";
        $count = 1;
        $allExists = false;
        foreach ($packageDbdeployFiles as $idx => $file) {
            if (!file_exists($dbDeployPath . basename($file))) {
                $count = $idx + 1;
                print "    ($count) " . 'Publishing ' . $moduleName . '/' . basename($file) . ' => ' . $dbDeployPath . basename($file) . PHP_EOL;
                copy($file, $dbDeployPath . basename($file));
                self::$count++;
                $allExists = false;
            } else {
                $allExists = true;
            }

        }

        if ($allExists) {
            print  "    Nothing to publish" . PHP_EOL;
        }

        print PHP_EOL;
    }

    /**
     * Converts a kebab cased string into CamelCased format
     *
     * @param $module
     *
     * @return array|string
     */
    private static function toModuleName($module)
    {
        $module = str_replace('-', ' ', $module);
        $module = explode(' ', ucwords($module));
        $module = implode('', $module);

        return $module;
    }

    /**
     * Executes the dbdeploy sql files inside self::CACHE_DELTAS_PATH
     * @return bool
     */
    private static function execDbDeploy()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $service = new MelisDbDeployDeployService();
        if (false === $service->isInstalled()) {
            $service->install();
        }

        $service->applyDeltaPath(realpath('dbdeploy' . DIRECTORY_SEPARATOR . self::CACHE_DELTAS_PATH));
        if ($service->changeLogCount() === self::getTotalDataFile()) {
            return true;
        } else {
            try {
                return self::execDbDeploy();
            } catch (\Exception $e) {
                print $e->getMessage();
            }
        }
    }

    /**
     * Returns the total count of dbdeploy sql files in self::CACHE_DELTAS_PATH
     * @return int
     */
    private static function getTotalDataFile()
    {
        $docRoot = self::docRoot();
        $dbDeployPath = $docRoot . '/dbdeploy/data/';
        if (!file_exists($dbDeployPath)) {
            return 0;
        }
        $files = glob($dbDeployPath . '*.sql');

        return count($files);
    }
}
