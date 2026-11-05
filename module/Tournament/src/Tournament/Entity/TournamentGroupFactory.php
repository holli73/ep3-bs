<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentGroupFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentGroup';
    protected static $entityPrimary = 'tgid';

}
