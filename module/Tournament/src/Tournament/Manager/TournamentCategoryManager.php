<?php

namespace Tournament\Manager;

use Base\Entity\AbstractEntity;
use Base\Manager\AbstractEntityManager;
use RuntimeException;
use Tournament\Entity\Tournament;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentCategoryFactory;
use Traversable;

class TournamentCategoryManager extends AbstractEntityManager
{

    protected function getInsertValues(AbstractEntity $entity)
    {
        return array(
            'tid' => $entity->need('tid'),
            'gender' => $entity->need('gender'),
            'status' => $entity->get('status', 'registration-open'),
            'group_size' => $entity->get('group_size', 4),
            'registration_deadline' => $entity->get('registration_deadline'),
        );
    }

    protected function getByResultSet(Traversable $resultSet)
    {
        return TournamentCategoryFactory::fromResultSet($resultSet);
    }

    protected function getByMetaResultSet(Traversable $metaResultSet, array $categories)
    {
        return TournamentCategoryFactory::fromMetaResultSet($categories, $metaResultSet);
    }

    /**
     * Gets the tournament category by primary id.
     *
     * @param int $tcid
     * @param boolean $strict
     * @return TournamentCategory
     * @throws RuntimeException
     */
    public function get($tcid, $strict = true)
    {
        $category = $this->getBy(array('tcid' => $tcid));

        if (empty($category)) {
            if ($strict) {
                throw new RuntimeException('This tournament category does not exist');
            }

            return null;
        } else {
            return current($category);
        }
    }

    /**
     * Gets all categories for a tournament, keyed by gender.
     *
     * @param Tournament $tournament
     * @return array
     */
    public function getByTournament(Tournament $tournament)
    {
        $categories = $this->getBy(array('tid' => $tournament->need('tid')));

        $categoriesByGender = array();

        foreach ($categories as $category) {
            $categoriesByGender[$category->need('gender')] = $category;
        }

        return $categoriesByGender;
    }

    /**
     * Ensures both a male and a female category exist for the passed tournament,
     * creating whichever ones are still missing.
     *
     * @param Tournament $tournament
     * @return array           The categories, keyed by gender.
     */
    public function ensureBothGenders(Tournament $tournament)
    {
        $categoriesByGender = $this->getByTournament($tournament);

        foreach (array_keys(TournamentCategory::$genderOptions) as $gender) {
            if (! isset($categoriesByGender[$gender])) {
                $category = new TournamentCategory(array(
                    'tid' => $tournament->need('tid'),
                    'gender' => $gender,
                ));

                $this->save($category);

                $categoriesByGender[$gender] = $category;
            }
        }

        return $categoriesByGender;
    }

}
