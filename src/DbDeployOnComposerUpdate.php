<?php

namespace MelisDbDeploy;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use MelisDbDeploy\Service\MelisDbDeployDeployService;
class DbDeployOnComposerUpdate
{
    const CACHE_DELTAS_PATH = 'data';

    public static function postUpdate(Event $event)
    {

        $composer = $_SERVER['DOCUMENT_ROOT'] . '/../composer.json';

        if(!file_exists($composer))
            return;

        $composer = json_decode(file_get_contents($composer), true);

        if(!isset($composer['repositories']) && !count($composer['repositories']))
            return;

        $repos = $composer['repositories'];

        foreach($repos as $idx => $repo) {
            // execute on this module
            $repo       = pathinfo($repo['url'], PATHINFO_FILENAME);
            echo self::copyDeltasFromPackage($repo);
        }

        print 'Executing DB Deploy' . PHP_EOL;

        $service = new MelisDbDeployDeployService();
        $service->applyDeltaPath(realpath('dbdeploy' . DIRECTORY_SEPARATOR . self::CACHE_DELTAS_PATH));

        print 'Done.' . PHP_EOL;

    }

    private static function copyDeltasFromPackage($module)
    {
        $melisVendorPath = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/melisplatform/'.$module;
        $dbDeployPath    = $_SERVER['DOCUMENT_ROOT'] . '/../dbdeploy/data/';

        if(!file_exists($melisVendorPath))
            return;

        if(!file_exists($dbDeployPath))
            mkdir($dbDeployPath, 0777, true);

        // copy dbdeploy file
        $packageDbdeployFiles = $melisVendorPath.'/install/dbdeploy/';


        if(!file_exists($packageDbdeployFiles))
            return null;

        echo $packageDbdeployFiles;
        $packageDbdeployFiles = glob($packageDbdeployFiles.'*.sql');

        foreach($packageDbdeployFiles as $file) {
            print 'Copying ' . basename($file) . ' => ' . $dbDeployPath . basename($file) . PHP_EOL;
            copy($file, $dbDeployPath . basename($file));
        }
    }
}