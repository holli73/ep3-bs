<?php

namespace Tournament\Manager;

use InvalidArgumentException;
use RuntimeException;
use Tournament\Entity\TournamentGroup;
use Tournament\Entity\TournamentGroupParticipant;
use Tournament\Entity\TournamentGroupParticipantFactory;
use Tournament\Entity\TournamentParticipant;
use Base\Manager\AbstractManager;
use Tournament\Table\TournamentGroupParticipantTable;
use Zend\Db\Sql\Predicate\In;

class TournamentGroupParticipantManager extends AbstractManager
{

    protected $groupParticipantTable;

    /**
     * Creates a new tournament group participant manager object.
     *
     * @param TournamentGroupParticipantTable $groupParticipantTable
     */
    public function __construct(TournamentGroupParticipantTable $groupParticipantTable)
    {
        $this->groupParticipantTable = $groupParticipantTable;
    }

    /**
     * Saves (updates or creates) a group assignment.
     *
     * @param TournamentGroupParticipant $assignment
     * @return TournamentGroupParticipant
     * @throws RuntimeException
     */
    public function save(TournamentGroupParticipant $assignment)
    {
        if ($assignment->get('tgpid')) {

            /* Update existing assignment */

            $updates = array();

            foreach ($assignment->need('updatedProperties') as $property) {
                $updates[$property] = $assignment->get($property);
            }

            if ($updates) {
                $this->groupParticipantTable->update($updates, array('tgpid' => $assignment->get('tgpid')));
            }

            $assignment->reset();

            $this->getEventManager()->trigger('save.update', $assignment);

        } else {

            /* Insert assignment */

            $this->groupParticipantTable->insert(array(
                'tgid' => $assignment->need('tgid'),
                'tpid' => $assignment->need('tpid'),
                'locked' => $assignment->get('locked', 0) ? 1 : 0,
                'created' => $assignment->get('created', date('Y-m-d H:i:s')),
            ));

            $tgpid = $this->groupParticipantTable->getLastInsertValue();

            if (! (is_numeric($tgpid) && $tgpid > 0)) {
                throw new RuntimeException('Failed to save tournament group assignment');
            }

            $assignment->add('tgpid', $tgpid);

            $this->getEventManager()->trigger('save.insert', $assignment);
        }

        $this->getEventManager()->trigger('save', $assignment);

        return $assignment;
    }

    /**
     * Gets the assignment by primary id.
     *
     * @param int $tgpid
     * @param boolean $strict
     * @return TournamentGroupParticipant
     * @throws RuntimeException
     */
    public function get($tgpid, $strict = true)
    {
        $assignment = $this->getBy(array('tgpid' => $tgpid));

        if (empty($assignment)) {
            if ($strict) {
                throw new RuntimeException('This tournament group assignment does not exist');
            }

            return null;
        } else {
            return current($assignment);
        }
    }

    /**
     * Gets all assignments that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->groupParticipantTable->getSql()->select();

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        if ($limit) {
            $select->limit($limit);

            if ($offset) {
                $select->offset($offset);
            }
        }

        $resultSet = $this->groupParticipantTable->selectWith($select);

        return TournamentGroupParticipantFactory::fromResultSet($resultSet);
    }

    /**
     * Gets the (single, since unique per participant) existing assignment for this
     * participant, or null if they are not assigned to any group yet.
     *
     * @param TournamentParticipant $participant
     * @return TournamentGroupParticipant|null
     */
    public function getByParticipant(TournamentParticipant $participant)
    {
        $assignments = $this->getBy(array('tpid' => $participant->need('tpid')));

        return $assignments ? current($assignments) : null;
    }

    /**
     * Gets all assignments for a single group.
     *
     * @param TournamentGroup $group
     * @return array
     */
    public function getByGroup(TournamentGroup $group)
    {
        return $this->getBy(array('tgid' => $group->need('tgid')));
    }

    /**
     * Gets all assignments across a set of groups (e.g. a whole category's groups).
     *
     * @param array $groups
     * @param boolean $onlyLocked           If true, only returns locked assignments.
     * @return array
     */
    public function getByGroups(array $groups, $onlyLocked = false)
    {
        if (empty($groups)) {
            return array();
        }

        $tgids = array();

        foreach ($groups as $group) {
            $tgids[] = $group->need('tgid');
        }

        $where = new In('tgid', $tgids);

        $assignments = $this->getBy($where);

        if (! $onlyLocked) {
            return $assignments;
        }

        return array_filter($assignments, function (TournamentGroupParticipant $assignment) {
            return (bool) $assignment->get('locked');
        });
    }

    /**
     * Deletes all non-locked assignments across a set of groups, keeping locked ones intact.
     *
     * @param array $groups
     */
    public function deleteUnlockedByGroups(array $groups)
    {
        if (empty($groups)) {
            return;
        }

        $tgids = array();

        foreach ($groups as $group) {
            $tgids[] = $group->need('tgid');
        }

        $where = new In('tgid', $tgids);

        $this->groupParticipantTable->delete(function ($delete) use ($where) {
            $delete->where($where);
            $delete->where(array('locked' => 0));
        });
    }

    /**
     * Deletes one assignment.
     *
     * @param int|TournamentGroupParticipant $assignment
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($assignment)
    {
        if ($assignment instanceof TournamentGroupParticipant) {
            $tgpid = $assignment->need('tgpid');
        } else {
            $tgpid = $assignment;
        }

        if (! (is_numeric($tgpid) && $tgpid > 0)) {
            throw new InvalidArgumentException('Assignment id must be numeric');
        }

        $assignment = $this->get($tgpid);

        $deletion = $this->groupParticipantTable->delete(array('tgpid' => $tgpid));

        $this->getEventManager()->trigger('delete', $assignment);

        return $deletion;
    }

}
