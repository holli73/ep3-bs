<?php

namespace Tournament\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class StandingsServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new StandingsService(
            $sm->get('Tournament\Manager\TournamentGroupManager'),
            $sm->get('Tournament\Manager\TournamentGroupParticipantManager'),
            $sm->get('Tournament\Manager\TournamentMatchManager'),
            $sm->get('Tournament\Manager\TournamentMatchSetManager'),
            $sm->get('Tournament\Manager\TournamentParticipantManager'));
    }

}
