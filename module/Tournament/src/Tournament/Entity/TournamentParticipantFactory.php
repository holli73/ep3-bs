<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentParticipantFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentParticipant';
    protected static $entityPrimary = 'tpid';

}
