<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentTable(TournamentTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
