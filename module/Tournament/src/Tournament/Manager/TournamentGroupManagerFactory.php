<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentGroupManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentGroupManager($sm->get('Tournament\Table\TournamentGroupTable'));
    }

}
