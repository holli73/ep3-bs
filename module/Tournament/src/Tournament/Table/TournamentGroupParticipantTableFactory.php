<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentGroupParticipantTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentGroupParticipantTable(TournamentGroupParticipantTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
