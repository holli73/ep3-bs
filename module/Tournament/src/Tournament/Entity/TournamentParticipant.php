<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentParticipant extends AbstractEntity
{

    protected $tpid;
    protected $tcid;
    protected $uid;
    protected $status;
    protected $registered_via;
    protected $created;

    protected $primary = 'tpid';

    /**
     * The possible status options.
     *
     * @var array
     */
    public static $statusOptions = array(
        'registered' => 'Registered',
        'withdrawn' => 'Withdrawn',
        'disqualified' => 'Disqualified',
    );

    /**
     * Returns the status string.
     *
     * @return string
     */
    public function getStatus()
    {
        $status = $this->need('status');

        return self::$statusOptions[$status] ?? 'Unknown';
    }

}
