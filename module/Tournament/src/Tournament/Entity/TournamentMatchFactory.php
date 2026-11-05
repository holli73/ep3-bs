<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentMatchFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentMatch';
    protected static $entityPrimary = 'tmaid';

}
