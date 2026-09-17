<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentGroupParticipant extends AbstractEntity
{

    protected $tgpid;
    protected $tgid;
    protected $tpid;
    protected $locked;
    protected $created;

    protected $primary = 'tgpid';

}
