<?php

namespace Tournament\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentManager(
            $sm->get('Tournament\Table\TournamentTable'),
            $sm->get('Tournament\Table\TournamentMetaTable'),
            $sm->get('Base\Manager\ConfigManager')->need('i18n.locale'));
    }

}
