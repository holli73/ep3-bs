<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentCategoryTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentCategoryTable(TournamentCategoryTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
