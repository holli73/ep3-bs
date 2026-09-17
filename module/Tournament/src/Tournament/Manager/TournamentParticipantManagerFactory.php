<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentParticipantManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentParticipantManager(
            $sm->get('Tournament\Table\TournamentParticipantTable'),
            $sm->get('Tournament\Table\TournamentParticipantMetaTable'));
    }

}
