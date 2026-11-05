<?php

namespace Tournament\Manager;

use Base\Entity\AbstractEntity;
use Base\Manager\AbstractEntityManager;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentParticipantFactory;
use Traversable;

class TournamentParticipantManager extends AbstractEntityManager
{

    protected function getInsertValues(AbstractEntity $entity)
    {
        return array(
            'tcid' => $entity->need('tcid'),
            'uid' => $entity->need('uid'),
            'status' => $entity->get('status', 'registered'),
            'registered_via' => $entity->get('registered_via', 'self'),
            'created' => $entity->get('created', date('Y-m-d H:i:s')),
        );
    }

    protected function getByResultSet(Traversable $resultSet)
    {
        return TournamentParticipantFactory::fromResultSet($resultSet);
    }

    protected function getByMetaResultSet(Traversable $metaResultSet, array $participants)
    {
        return TournamentParticipantFactory::fromMetaResultSet($participants, $metaResultSet);
    }

    /**
     * Gets the tournament participant by primary id.
     *
     * @param int $tpid
     * @param boolean $strict
     * @return \Tournament\Entity\TournamentParticipant
     * @throws RuntimeException
     */
    public function get($tpid, $strict = true)
    {
        $participant = $this->getBy(array('tpid' => $tpid));

        if (empty($participant)) {
            if ($strict) {
                throw new RuntimeException('This tournament participant does not exist');
            }

            return null;
        } else {
            return current($participant);
        }
    }

    /**
     * Gets the participants of a category, regardless of status.
     *
     * @param TournamentCategory $category
     * @param string $status               If passed, filters by this status.
     * @return array
     */
    public function getByCategory(TournamentCategory $category, $status = null)
    {
        $where = array('tcid' => $category->need('tcid'));

        if ($status) {
            $where['status'] = $status;
        }

        return $this->getBy($where, 'created ASC');
    }

    /**
     * Gets the (single, since unique per category+user) existing participant row
     * for this user in this category, regardless of status, or null if none exists.
     *
     * @param TournamentCategory $category
     * @param int $uid
     * @return \Tournament\Entity\TournamentParticipant|null
     */
    public function getByCategoryAndUser(TournamentCategory $category, $uid)
    {
        $participants = $this->getBy(array('tcid' => $category->need('tcid'), 'uid' => $uid));

        return $participants ? current($participants) : null;
    }

}
