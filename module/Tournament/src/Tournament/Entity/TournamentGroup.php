<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentGroup extends AbstractEntity
{

    protected $tgid;
    protected $tcid;
    protected $label;
    protected $created;

    protected $primary = 'tgid';

}
