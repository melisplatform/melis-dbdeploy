<?php

namespace MelisDbDeploy;

use Composer\Script\Event;
use MelisDbDeploy\Service\MelisDbDeployDeployService;
class DbDeployOnComposerUpdate
{
    const CACHE_DELTAS_PATH = 'data';

    public static function postUpdate(Event $event)
    {
        $composer = $_SERVER['PWD'] . '/composer.json';

        if(!file_exists($composer))
            return;

        $composer = json_decode(file_get_contents($composer), true);

        if(!isset($composer['repositories']) || !count($composer['repositories']))
            return;

        $repos = $composer['repositories'];

        foreach($repos as $idx => $repo) {
            // execute on this module
            $repo       = pathinfo($repo['url'], PATHINFO_FILENAME);
            self::copyDeltasFromPackage($repo);
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
        $dbDeployPath = $_SERVER['PWD'] . '/dbdeploy/data/';

        if(!file_exists($dbDeployPath))
            return 0;

        $files = glob($dbDeployPath.'*.sql');

        return count($files);

    }

    private static function copyDeltasFromPackage($module)
    {
        $melisVendorPath = $_SERVER['PWD'] . '/vendor/melisplatform/'.$module;
        $dbDeployPath    = $_SERVER['PWD'] . '/dbdeploy/data/';

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
}