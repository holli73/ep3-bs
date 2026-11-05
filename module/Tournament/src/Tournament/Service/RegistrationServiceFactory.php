<?php

namespace Tournament\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class RegistrationServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new RegistrationService($sm->get('Tournament\Manager\TournamentParticipantManager'));
    }

}
