<?php

namespace Tournament\Entity;

use Base\Entity\AbstractEntity;

class TournamentCategory extends AbstractEntity
{

    protected $tcid;
    protected $tid;
    protected $gender;
    protected $status;
    protected $group_size;
    protected $registration_deadline;

    protected $primary = 'tcid';

    /**
     * The possible gender options.
     *
     * @var array
     */
    public static $genderOptions = array(
        'male' => 'Male',
        'female' => 'Female',
    );

    /**
     * Returns the gender string.
     *
     * @return string
     */
    public function getGender()
    {
        $gender = $this->need('gender');

        return self::$genderOptions[$gender] ?? 'Unknown';
    }

    /**
     * The possible status options.
     *
     * @var array
     */
    public static $statusOptions = array(
        'registration-open' => 'Registration open',
        'registration-closed' => 'Registration closed',
        'group-phase' => 'Group phase',
        'knockout-phase' => 'Knockout phase',
        'completed' => 'Completed',
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
