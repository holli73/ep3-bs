<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntityFactory;

class TournamentCategoryFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Tournament\Entity\TournamentCategory';
    protected static $entityPrimary = 'tcid';

}
