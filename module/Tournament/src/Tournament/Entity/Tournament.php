<?php

namespace Tournament\Entity;

use Base\Entity\AbstractLocaleEntity;

class Tournament extends AbstractLocaleEntity
{

    protected $tid;
    protected $status;
    protected $date_start;
    protected $date_end;
    protected $registration_deadline;
    protected $created;

    protected $primary = 'tid';

    /**
     * The possible status options.
     *
     * @var array
     */
    public static $statusOptions = array(
        'draft' => 'Draft',
        'registration-open' => 'Registration open',
        'registration-closed' => 'Registration closed',
        'group-phase' => 'Group phase',
        'knockout-phase' => 'Knockout phase',
        'completed' => 'Completed',
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

}
