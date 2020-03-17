<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Controller;

use MelisCore\ServiceManagerGrabber;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
class MelisDbDeployControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = false;
    protected $sm;
    protected $method = 'save';

    public function setUp()
    {
        $this->sm  = new ServiceManagerGrabber();
    }

    

    public function getPayload($method)
    {
        return $this->sm->getPhpUnitTool()->getPayload('MelisDbDeploy', $method);
    }

    /**
     * START ADDING YOUR TESTS HERE
     */

    public function testBasicMelisDbDeploySuccess()
    {
        $this->assertEquals("equalvalue", "equalvalue");
    }


}

