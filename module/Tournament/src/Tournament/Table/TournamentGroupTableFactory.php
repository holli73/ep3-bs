<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentGroupTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentGroupTable(TournamentGroupTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
