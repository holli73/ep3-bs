<?php

namespace Tournament\Manager;

use Base\Manager\AbstractManager;
use InvalidArgumentException;
use RuntimeException;
use Tournament\Entity\TournamentMatch;
use Tournament\Entity\TournamentMatchSet;
use Tournament\Entity\TournamentMatchSetFactory;
use Tournament\Table\TournamentMatchSetTable;

class TournamentMatchSetManager extends AbstractManager
{

    protected $matchSetTable;

    /**
     * Creates a new tournament match set manager object.
     *
     * @param TournamentMatchSetTable $matchSetTable
     */
    public function __construct(TournamentMatchSetTable $matchSetTable)
    {
        $this->matchSetTable = $matchSetTable;
    }

    /**
     * Saves (updates or creates) a match set.
     *
     * @param TournamentMatchSet $matchSet
     * @return TournamentMatchSet
     * @throws RuntimeException
     */
    public function save(TournamentMatchSet $matchSet)
    {
        if ($matchSet->get('tmsid')) {

            $updates = array();

            foreach ($matchSet->need('updatedProperties') as $property) {
                $updates[$property] = $matchSet->get($property);
            }

            if ($updates) {
                $this->matchSetTable->update($updates, array('tmsid' => $matchSet->get('tmsid')));
            }

            $matchSet->reset();

            $this->getEventManager()->trigger('save.update', $matchSet);

        } else {

            $this->matchSetTable->insert(array(
                'tmaid' => $matchSet->need('tmaid'),
                'set_number' => $matchSet->need('set_number'),
                'games_a' => $matchSet->need('games_a'),
                'games_b' => $matchSet->need('games_b'),
                'is_match_tiebreak' => $matchSet->get('is_match_tiebreak', 0) ? 1 : 0,
            ));

            $tmsid = $this->matchSetTable->getLastInsertValue();

            if (! (is_numeric($tmsid) && $tmsid > 0)) {
                throw new RuntimeException('Failed to save tournament match set');
            }

            $matchSet->add('tmsid', $tmsid);

            $this->getEventManager()->trigger('save.insert', $matchSet);
        }

        $this->getEventManager()->trigger('save', $matchSet);

        return $matchSet;
    }

    /**
     * Gets the match set by primary id.
     *
     * @param int $tmsid
     * @param boolean $strict
     * @return TournamentMatchSet
     * @throws RuntimeException
     */
    public function get($tmsid, $strict = true)
    {
        $matchSet = $this->getBy(array('tmsid' => $tmsid));

        if (empty($matchSet)) {
            if ($strict) {
                throw new RuntimeException('This tournament match set does not exist');
            }

            return null;
        } else {
            return current($matchSet);
        }
    }

    /**
     * Gets all match sets that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->matchSetTable->getSql()->select();

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

        $resultSet = $this->matchSetTable->selectWith($select);

        return TournamentMatchSetFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all sets of a match, ordered by set number.
     *
     * @param TournamentMatch $match
     * @return array
     */
    public function getByMatch(TournamentMatch $match)
    {
        return $this->getBy(array('tmaid' => $match->need('tmaid')), 'set_number ASC');
    }

    /**
     * Replaces all of a match's sets with the passed set data in one operation
     * (delete-all-then-insert), since editing a result is always a full replace.
     *
     * @param TournamentMatch $match
     * @param array $setsData          Each item: ['games_a' => int, 'games_b' => int, 'is_match_tiebreak' => bool]
     * @return array                   The newly created TournamentMatchSet entities.
     */
    public function replaceSets(TournamentMatch $match, array $setsData)
    {
        $this->matchSetTable->delete(array('tmaid' => $match->need('tmaid')));

        $sets = array();

        foreach ($setsData as $index => $setData) {
            $set = new TournamentMatchSet(array(
                'tmaid' => $match->need('tmaid'),
                'set_number' => $index + 1,
                'games_a' => $setData['games_a'],
                'games_b' => $setData['games_b'],
                'is_match_tiebreak' => ! empty($setData['is_match_tiebreak']),
            ));

            $this->save($set);

            $sets[] = $set;
        }

        return $sets;
    }

    /**
     * Deletes one match set.
     *
     * @param int|TournamentMatchSet $matchSet
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($matchSet)
    {
        if ($matchSet instanceof TournamentMatchSet) {
            $tmsid = $matchSet->need('tmsid');
        } else {
            $tmsid = $matchSet;
        }

        if (! (is_numeric($tmsid) && $tmsid > 0)) {
            throw new InvalidArgumentException('Match set id must be numeric');
        }

        $matchSet = $this->get($tmsid);

        $deletion = $this->matchSetTable->delete(array('tmsid' => $tmsid));

        $this->getEventManager()->trigger('delete', $matchSet);

        return $deletion;
    }

}
