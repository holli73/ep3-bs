<?php

namespace Tournament\Manager;

use Base\Entity\AbstractEntity;
use Base\Manager\AbstractLocaleEntityManager;
use RuntimeException;
use Tournament\Entity\TournamentFactory;
use Traversable;

class TournamentManager extends AbstractLocaleEntityManager
{

    protected function getInsertValues(AbstractEntity $entity)
    {
        return array(
            'status' => $entity->get('status', 'draft'),
            'date_start' => $entity->need('date_start'),
            'date_end' => $entity->need('date_end'),
            'registration_deadline' => $entity->get('registration_deadline'),
            'created' => $entity->get('created', date('Y-m-d H:i:s')),
        );
    }

    protected function getByResultSet(Traversable $resultSet)
    {
        return TournamentFactory::fromResultSet($resultSet);
    }

    protected function getByMetaResultSet(Traversable $metaResultSet, array $tournaments)
    {
        return TournamentFactory::fromMetaResultSet($tournaments, $metaResultSet);
    }

    /**
     * Gets the tournament by primary id.
     *
     * @param int $tid
     * @param boolean $strict
     * @return \Tournament\Entity\Tournament
     * @throws RuntimeException
     */
    public function get($tid, $strict = true)
    {
        $tournament = $this->getBy(array('tid' => $tid));

        if (empty($tournament)) {
            if ($strict) {
                throw new RuntimeException('This tournament does not exist');
            }

            return null;
        } else {
            return current($tournament);
        }
    }

}
