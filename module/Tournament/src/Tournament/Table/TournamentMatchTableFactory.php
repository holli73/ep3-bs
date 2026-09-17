<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentMatchTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentMatchTable(TournamentMatchTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
