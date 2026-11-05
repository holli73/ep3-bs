<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentGroupParticipantManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentGroupParticipantManager($sm->get('Tournament\Table\TournamentGroupParticipantTable'));
    }

}
