<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentMatch extends AbstractEntity
{

    protected $tmaid;
    protected $tcid;
    protected $phase;
    protected $tgid;
    protected $round;
    protected $bracket_slot;
    protected $player_a_tpid;
    protected $player_b_tpid;
    protected $winner_tpid;
    protected $status;
    protected $feeds_into_tmaid;
    protected $feeds_into_slot;
    protected $created;

    protected $primary = 'tmaid';

    /**
     * The possible status options.
     *
     * @var array
     */
    public static $statusOptions = array(
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'walkover' => 'Walkover',
        'cancelled' => 'Cancelled',
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

    /**
     * The possible round labels (knockout phase only).
     *
     * @var array
     */
    public static $roundOptions = array(
        'qf' => 'Quarterfinal',
        'sf' => 'Semifinal',
        'final' => 'Final',
    );

}
