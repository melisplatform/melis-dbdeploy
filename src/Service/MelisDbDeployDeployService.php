<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service;

use MelisCore\Service\MelisServiceManager;
use MelisDbDeploy\PhingListener;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

class MelisDbDeployDeployService extends MelisServiceManager
{
    /**
     *
     * The tablename to use from the database for storing all changes
     * This cannot be changed due to Phing Task Dependencie
     *
     * @var string
     */
    const TABLE_NAME = 'changelog';
    const OUTPUT_FILENAME = 'melisplatform-dbdeploy.sql';
    const OUTPUT_FILENAME_UNDO = 'melisplatform-dbdeploy-reverse.sql';
    const DRIVER = 'pdo';

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var \DbDeployTask
     */
    protected $dbDeployTask;

    /**
     * @var Array
     */
    protected $appConfig;

    public function __construct()
    {
        $this->prepare();
    }

    protected function prepare()
    {
        $configurations = glob("config/autoload/platforms/*.php");

        if (!empty($configurations)) {
            $path = count($configurations) > 1 ? "config/autoload/platforms/" . getenv('MELIS_PLATFORM') . '.php'
                : current($configurations);
            $appConfig = include $path;
            $this->appConfig = $appConfig;

            // Overriding database connection driver to PDO
            $this->db = new Adapter(array_merge($appConfig['db'], [
                'driver' => static::DRIVER,
            ]));

            $cwd = getcwd();
            set_include_path("$cwd/vendor/phing/phing/classes/");

            $target = new \Target();
            $target->setName('default');

            $project = new \Project();

            $ctx = new \PhingXMLContext($project);
            $project->addReference("phing.parsing.context", $ctx);

            $project->addTarget('default', $target);
            $project->setDefaultTarget('default');
            $project->addBuildListener(new PhingListener());

            $this->dbDeployTask = new \DbDeployTask();
            $this->dbDeployTask->setProject($project);
            $this->dbDeployTask->setUrl(sprintf('mysql:dbname=%s;host=%s;charset=utf8', $appConfig['db']['database'], $appConfig['db']['hostname']));
            $this->dbDeployTask->setUserId($appConfig['db']['username']);
            $this->dbDeployTask->setPassword($appConfig['db']['password']);
            $this->dbDeployTask->setOutputFile(static::OUTPUT_FILENAME);
            $this->dbDeployTask->setUndoOutputFile(static::OUTPUT_FILENAME_UNDO);
            $this->dbDeployTask->setOwningTarget($target);
            $this->dbDeployTask->setCheckAll(true);
            $this->dbDeployTask->setAppliedBy('MelisDbDeploy');

            //$this->db->query('Set Global max_connections=500;');

        }

    }

    public function isInstalled()
    {
        try {
            $this->db->query(
                'describe ' . self::TABLE_NAME,
                Adapter::QUERY_MODE_EXECUTE
            );
        } catch (\PDOException $invalidQueryException) {
            return false;
        }

        return true;
    }

    public function install()
    {
        $sqlCreateTableChangelog = file_get_contents(dirname(dirname(__DIR__))
            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR
            . 'changelog.sql'
        );
        $this->db->query($sqlCreateTableChangelog, Adapter::QUERY_MODE_EXECUTE);
    }

    public function changeLogCount()
    {
        try {
            $data = $this->db->query(
                'SELECT COUNT(`change_number`) as total from `changelog`',
                Adapter::QUERY_MODE_EXECUTE
            )->current();

            if ($data) {
                return (int) $data->total;
            }

        } catch (\PDOException $invalidQueryException) {
            return 0;
        }

        return 0;
    }

    public function applyDeltaPath($deltaPath)
    {

        \Phing::startup();

        $cwd = getcwd();
        $workingDirectory = $cwd . DIRECTORY_SEPARATOR . 'dbdeploy';
        chdir($workingDirectory);

        if (!file_exists($workingDirectory)) {
            throw new \Exception(sprintf(
                'The directory %s must exist to store temporary database migration file',
                $workingDirectory
            ));
        }

        try {
            $this->execute($deltaPath);
            $this->db->getDriver()->getConnection()->disconnect();
        } catch (\Exception $e) {
            print $e->getMessage();
        }

        chdir($cwd);

        \Phing::shutdown();
    }

    protected function execute($path)
    {
        $this->dbDeployTask->setDir($path);
        $this->dbDeployTask->main();

        $filename = realpath(static::OUTPUT_FILENAME);

        $file = new \PhingFile($filename);

        $execTask = new \PDOSQLExecTask();
        $execTask->setProject($this->dbDeployTask->getProject());
        $execTask->setOwningTarget($this->dbDeployTask->getOwningTarget());
        $execTask->setUrl(sprintf('mysql:dbname=%s;host=%s;charset=utf8', $appConfig['db']['database'], $appConfig['db']['hostname']));
        $execTask->setUserid($this->appConfig['db']['username']);
        $execTask->setPassword($this->appConfig['db']['password']);
        $execTask->setSrc($file);

        try {
            $execTask->main();
            if (file_exists($filename)) {
                @unlink($filename);
            }

        } catch (\Exception $e) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/../dbdeploy/';
            $logError = false;

            if ($logError) {
                if (file_exists($path)) {
                    file_put_contents($path . 'dbdeploy_error.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }


        }


    }
}
