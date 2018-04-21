<?php

namespace MelisDbDeploy;

use Composer\Script\Event;
use MelisDbDeploy\Service\MelisDbDeployDeployService;
class DbDeployOnComposerUpdate
{
    const CACHE_DELTAS_PATH = 'data';

    public static function postUpdate(Event $event)
    {
        $docRoot  = self::docRoot();
        $composer = $docRoot . 'composer.json';

        if(!file_exists($composer))
            return;

        $composer = json_decode(file_get_contents($composer), true);

        if(!isset($composer['require']) || !count($composer['require']))
            return;

        $repos = $composer['require'];

        foreach($repos as $repo => $version) {
            // execute on this module
            $path       = pathinfo($repo, PATHINFO_FILENAME);

            self::copyDeltasFromPackage($path);
        }

        print 'Executing DB Deploy' . PHP_EOL;
        if(self::execDbDeploy()) {
            print 'Done.' . PHP_EOL;
        }

    }

    private static function execDbDeploy()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $service = new MelisDbDeployDeployService();

        if(false === $service->isInstalled())
            $service->install();

        $service->applyDeltaPath(realpath('dbdeploy' . DIRECTORY_SEPARATOR . self::CACHE_DELTAS_PATH));

        if($service->changeLogCount() === self::getTotalDataFile()) {
            return true;
        }
        else {
            self::execDbDeploy();
        }
    }

    private static function getTotalDataFile()
    {
        $docRoot      = self::docRoot();
        $dbDeployPath = $docRoot . '/dbdeploy/data/';

        if(!file_exists($dbDeployPath))
            return 0;

        $files = glob($dbDeployPath.'*.sql');

        return count($files);

    }

    private static function copyDeltasFromPackage($module)
    {
        $docRoot         = self::docRoot();
        $melisVendorPath = $docRoot . 'vendor/melisplatform/'.$module;
        $dbDeployPath    = $docRoot . 'dbdeploy/data/';

        if(!file_exists($melisVendorPath))
            return;

        if(!file_exists($dbDeployPath))
            mkdir($dbDeployPath, 0777, true);

        // copy dbdeploy file
        $packageDbdeployFiles = $melisVendorPath.'/install/dbdeploy/';


        if(!file_exists($packageDbdeployFiles))
            return null;

        $packageDbdeployFiles = glob($packageDbdeployFiles.'*.sql');

        foreach($packageDbdeployFiles as $file) {
            print 'Copying ' . $module.'/'.basename($file) . ' => ' . $dbDeployPath . basename($file) . PHP_EOL;
            copy($file, $dbDeployPath . basename($file));
        }
    }

    private static function docRoot()
    {
        $docRoot = dirname(__DIR__);
        $parts   = explode(DIRECTORY_SEPARATOR, $docRoot);

        $path = null;
        foreach($parts as $idx => $part) {
            $path .= $part . DIRECTORY_SEPARATOR;
            if($part == 'vendor')
                break;
        }

        $path = str_replace(DIRECTORY_SEPARATOR.'vendor', '', $path);

        return $path;
    }
}