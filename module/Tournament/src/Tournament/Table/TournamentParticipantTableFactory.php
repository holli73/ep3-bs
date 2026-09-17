<?php

namespace Tournament\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TournamentParticipantTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new TournamentParticipantTable(TournamentParticipantTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
