<?php

namespace Tournament\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BracketServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new BracketService(
            $sm->get('Tournament\Manager\TournamentMatchManager'),
            $sm->get('Tournament\Manager\TournamentMatchSetManager'),
            $sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection());
    }

}
