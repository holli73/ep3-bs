<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentCategoryManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentCategoryManager(
            $sm->get('Tournament\Table\TournamentCategoryTable'),
            $sm->get('Tournament\Table\TournamentCategoryMetaTable'));
    }

}
