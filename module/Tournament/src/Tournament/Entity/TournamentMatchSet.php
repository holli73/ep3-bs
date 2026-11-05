<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentMatchSet extends AbstractEntity
{

    protected $tmsid;
    protected $tmaid;
    protected $set_number;
    protected $games_a;
    protected $games_b;
    protected $is_match_tiebreak;

    protected $primary = 'tmsid';

}
