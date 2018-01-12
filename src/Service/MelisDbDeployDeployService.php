<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Service;

use MelisCore\Service\MelisCoreGeneralService;
use MelisDbDeploy\ConfigFileNotFoundException;
use MelisDbDeploy\PhingListener;
use Zend\Db\Adapter\Adapter;

class MelisDbDeployDeployService extends MelisCoreGeneralService
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


    public function setDbPrepare($dbProvider, $host, $database, $username, $password)
    {
        $databaseConf = array(
            'db' => array(
                'dsn' => "$dbProvider:dbname=$database;host=$host",
                'username' => $username,
                'password' => $password
            )
        );
        $this->prepare($databaseConf);
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

    public function applyDeltaPath($deltaPath)
    {
        \Phing::startup();

        $cwd = getcwd();
        $workingDirectory = $cwd . DIRECTORY_SEPARATOR . 'cache';
        chdir($workingDirectory);

        if (!file_exists($workingDirectory)) {
            throw new \Exception(sprintf(
                'The directory %s must exist to store temporary database migration file',
                $workingDirectory
            ));
        }

        $this->execute($deltaPath);

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
        $execTask->setUrl($this->appConfig['db']['dsn']);
        $execTask->setUserid($this->appConfig['db']['username']);
        $execTask->setPassword($this->appConfig['db']['password']);
        $execTask->setSrc($file);

        try {

            $execTask->main();
            if(file_exists($filename))
                unlink($filename);
        }catch(\Exception $e) {
            echo $e->getMessage();
        }


    }

    protected function prepare($databaseConfig = array())
    {
        $configurations = array();

        if(empty($databaseConfig)) {
            $configurations = glob("config/autoload/platforms/*.php");
            if (empty($configurations)) {
                throw new ConfigFileNotFoundException();
            }
        }

        if($configurations) {
            $path = current($configurations);
            $appConfig = include $path;
        }

        if($databaseConfig) {
            $appConfig = $databaseConfig;
        }

        print_r($databaseConfig);

        $this->appConfig = $appConfig;

        $this->db = new Adapter($appConfig['db'] + [
                'driver' => static::DRIVER,
        ]);

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
        $this->dbDeployTask->setUrl($appConfig['db']['dsn']);
        $this->dbDeployTask->setUserId($appConfig['db']['username']);
        $this->dbDeployTask->setPassword($appConfig['db']['password']);
        $this->dbDeployTask->setOutputFile(static::OUTPUT_FILENAME);
        $this->dbDeployTask->setUndoOutputFile(static::OUTPUT_FILENAME_UNDO);
        $this->dbDeployTask->setOwningTarget($target);
        $this->dbDeployTask->setCheckAll(true);
    }
}
