<?php

namespace Tournament\Manager;

use Base\Manager\AbstractManager;
use InvalidArgumentException;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentGroup;
use Tournament\Entity\TournamentGroupFactory;
use Tournament\Table\TournamentGroupTable;

class TournamentGroupManager extends AbstractManager
{

    protected $groupTable;

    /**
     * Creates a new tournament group manager object.
     *
     * @param TournamentGroupTable $groupTable
     */
    public function __construct(TournamentGroupTable $groupTable)
    {
        $this->groupTable = $groupTable;
    }

    /**
     * Saves (updates or creates) a group.
     *
     * @param TournamentGroup $group
     * @return TournamentGroup
     * @throws RuntimeException
     */
    public function save(TournamentGroup $group)
    {
        if ($group->get('tgid')) {

            /* Update existing group */

            $updates = array();

            foreach ($group->need('updatedProperties') as $property) {
                $updates[$property] = $group->get($property);
            }

            if ($updates) {
                $this->groupTable->update($updates, array('tgid' => $group->get('tgid')));
            }

            $group->reset();

            $this->getEventManager()->trigger('save.update', $group);

        } else {

            /* Insert group */

            $this->groupTable->insert(array(
                'tcid' => $group->need('tcid'),
                'label' => $group->need('label'),
                'created' => $group->get('created', date('Y-m-d H:i:s')),
            ));

            $tgid = $this->groupTable->getLastInsertValue();

            if (! (is_numeric($tgid) && $tgid > 0)) {
                throw new RuntimeException('Failed to save tournament group');
            }

            $group->add('tgid', $tgid);

            $this->getEventManager()->trigger('save.insert', $group);
        }

        $this->getEventManager()->trigger('save', $group);

        return $group;
    }

    /**
     * Gets the tournament group by primary id.
     *
     * @param int $tgid
     * @param boolean $strict
     * @return TournamentGroup
     * @throws RuntimeException
     */
    public function get($tgid, $strict = true)
    {
        $group = $this->getBy(array('tgid' => $tgid));

        if (empty($group)) {
            if ($strict) {
                throw new RuntimeException('This tournament group does not exist');
            }

            return null;
        } else {
            return current($group);
        }
    }

    /**
     * Gets all groups that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->groupTable->getSql()->select();

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

        $resultSet = $this->groupTable->selectWith($select);

        return TournamentGroupFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all groups of a category, ordered by label.
     *
     * @param TournamentCategory $category
     * @return array
     */
    public function getByCategory(TournamentCategory $category)
    {
        return $this->getBy(array('tcid' => $category->need('tcid')), 'label ASC');
    }

    /**
     * Deletes one group.
     *
     * @param int|TournamentGroup $group
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($group)
    {
        if ($group instanceof TournamentGroup) {
            $tgid = $group->need('tgid');
        } else {
            $tgid = $group;
        }

        if (! (is_numeric($tgid) && $tgid > 0)) {
            throw new InvalidArgumentException('Group id must be numeric');
        }

        $group = $this->get($tgid);

        $deletion = $this->groupTable->delete(array('tgid' => $tgid));

        $this->getEventManager()->trigger('delete', $group);

        return $deletion;
    }

}
