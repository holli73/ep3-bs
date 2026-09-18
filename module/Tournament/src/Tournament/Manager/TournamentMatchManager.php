<?php

namespace Tournament\Manager;

use Base\Entity\AbstractEntity;
use Base\Manager\AbstractEntityManager;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentGroup;
use Tournament\Entity\TournamentMatchFactory;
use Tournament\Entity\TournamentParticipant;
use Traversable;
use Zend\Db\Sql\Where;

class TournamentMatchManager extends AbstractEntityManager
{

    protected function getInsertValues(AbstractEntity $entity)
    {
        return array(
            'tcid' => $entity->need('tcid'),
            'phase' => $entity->need('phase'),
            'tgid' => $entity->get('tgid'),
            'round' => $entity->get('round'),
            'bracket_slot' => $entity->get('bracket_slot'),
            'player_a_tpid' => $entity->get('player_a_tpid'),
            'player_b_tpid' => $entity->get('player_b_tpid'),
            'winner_tpid' => $entity->get('winner_tpid'),
            'status' => $entity->get('status', 'scheduled'),
            'feeds_into_tmaid' => $entity->get('feeds_into_tmaid'),
            'feeds_into_slot' => $entity->get('feeds_into_slot'),
            'created' => $entity->get('created', date('Y-m-d H:i:s')),
        );
    }

    protected function getByResultSet(Traversable $resultSet)
    {
        return TournamentMatchFactory::fromResultSet($resultSet);
    }

    protected function getByMetaResultSet(Traversable $metaResultSet, array $matches)
    {
        return TournamentMatchFactory::fromMetaResultSet($matches, $metaResultSet);
    }

    /**
     * Gets the tournament match by primary id.
     *
     * @param int $tmaid
     * @param boolean $strict
     * @return \Tournament\Entity\TournamentMatch
     * @throws RuntimeException
     */
    public function get($tmaid, $strict = true)
    {
        $match = $this->getBy(array('tmaid' => $tmaid));

        if (empty($match)) {
            if ($strict) {
                throw new RuntimeException('This tournament match does not exist');
            }

            return null;
        } else {
            return current($match);
        }
    }

    /**
     * Gets all matches of a category, optionally filtered by phase.
     *
     * @param TournamentCategory $category
     * @param string $phase                'group' or 'knockout'
     * @return array
     */
    public function getByCategory(TournamentCategory $category, $phase = null)
    {
        $where = array('tcid' => $category->need('tcid'));

        if ($phase) {
            $where['phase'] = $phase;
        }

        return $this->getBy($where, 'tmaid ASC');
    }

    /**
     * Gets all group-phase matches of a single group.
     *
     * @param TournamentGroup $group
     * @return array
     */
    public function getByGroup(TournamentGroup $group)
    {
        return $this->getBy(array('tgid' => $group->need('tgid'), 'phase' => 'group'), 'tmaid ASC');
    }

    /**
     * Gets all matches (any phase) a participant plays in, either as player A or B.
     *
     * @param TournamentParticipant $participant
     * @return array
     */
    public function getByParticipant(TournamentParticipant $participant)
    {
        $tpid = $participant->need('tpid');

        $where = new Where();
        $where->equalTo('player_a_tpid', $tpid);
        $where->OR;
        $where->equalTo('player_b_tpid', $tpid);

        return $this->getBy($where, 'tmaid ASC');
    }

}
