<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentGroupParticipantFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentGroupParticipant';
    protected static $entityPrimary = 'tgpid';

}
