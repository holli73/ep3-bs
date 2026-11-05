<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentCategoryMetaTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentCategoryMetaTable(TournamentCategoryMetaTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
