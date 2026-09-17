<?php

namespace Tournament\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DrawServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new DrawService(
            $sm->get('Tournament\Manager\TournamentGroupManager'),
            $sm->get('Tournament\Manager\TournamentGroupParticipantManager'),
            $sm->get('Tournament\Manager\TournamentParticipantManager'),
            $sm->get('Tournament\Manager\TournamentMatchManager'),
            $sm->get('User\Manager\UserManager'),
            $sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection());
    }

}
