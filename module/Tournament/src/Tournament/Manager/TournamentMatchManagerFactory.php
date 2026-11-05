<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentMatchManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentMatchManager(
            $sm->get('Tournament\Table\TournamentMatchTable'),
            $sm->get('Tournament\Table\TournamentMatchMetaTable'));
    }

}
