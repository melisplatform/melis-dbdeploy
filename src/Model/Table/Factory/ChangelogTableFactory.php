<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy\Model\Table\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Stdlib\Hydrator\ObjectProperty;

use MelisDbDeploy\Model\Changelog;
use MelisDbDeploy\Model\Table\ChangelogTable;

class ChangelogTableFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{
    	$hydratingResultSet = new HydratingResultSet(new ObjectProperty(), new Changelog());
    	$tableGateway = new TableGateway('changelog', $sl->get('Zend\Db\Adapter\Adapter'), null, $hydratingResultSet);
		
    	return new ChangelogTable($tableGateway);
	}

}