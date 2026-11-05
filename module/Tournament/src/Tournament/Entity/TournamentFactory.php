<?php

namespace Tournament\Entity;

use Base\Entity\AbstractLocaleEntityFactory;

class TournamentFactory extends AbstractLocaleEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\Tournament';
    protected static $entityPrimary = 'tid';

}
