<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentMatchMetaTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentMatchMetaTable(TournamentMatchMetaTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
