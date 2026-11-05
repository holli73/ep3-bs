<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentMatchSetFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentMatchSet';
    protected static $entityPrimary = 'tmsid';

}
