<?php

namespace Tournament\Service;

use Base\Service\AbstractService;
use Exception;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentMatch;
use Tournament\Manager\TournamentMatchManager;
use Tournament\Manager\TournamentMatchSetManager;
use Zend\Db\Adapter\Driver\ConnectionInterface;

class BracketService extends AbstractService
{

    protected $matchManager;
    protected $matchSetManager;
    protected $connection;

    /**
     * Creates a new bracket service object.
     *
     * @param TournamentMatchManager $matchManager
     * @param TournamentMatchSetManager $matchSetManager
     * @param ConnectionInterface $connection
     */
    public function __construct(
        TournamentMatchManager $matchManager,
        TournamentMatchSetManager $matchSetManager,
        ConnectionInterface $connection
    ) {
        $this->matchManager = $matchManager;
        $this->matchSetManager = $matchSetManager;
        $this->connection = $connection;
    }

    /**
     * Records a match's result: replaces its sets, sets the winner and status,
     * and — only for knockout matches with a wired-up next match — propagates
     * the winner into that next match's player slot. Group matches simply have
     * no feeds_into_tmaid, so the propagation step is a no-op for them.
     *
     * @param TournamentMatch $match
     * @param array $setsData           Each item: ['games_a' => int, 'games_b' => int, 'is_match_tiebreak' => bool]
     * @param int $winnerTpid
     * @return TournamentMatch
     */
    public function recordResult(TournamentMatch $match, array $setsData, $winnerTpid)
    {
        if (! $this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $transaction = true;
        } else {
            $transaction = false;
        }

        try {
            $this->matchSetManager->replaceSets($match, $setsData);

            $match->set('winner_tpid', $winnerTpid);
            $match->set('status', 'completed');

            $this->matchManager->save($match);

            if ($match->need('phase') == 'knockout' && $match->get('feeds_into_tmaid')) {
                $nextMatch = $this->matchManager->get($match->need('feeds_into_tmaid'));

                $slotProperty = $match->need('feeds_into_slot') == 'A' ? 'player_a_tpid' : 'player_b_tpid';

                $nextMatch->set($slotProperty, $winnerTpid);

                $this->matchManager->save($nextMatch);
            }

            if ($transaction) {
                $this->connection->commit();
                $transaction = false;
            }
        } catch (Exception $e) {
            if ($transaction) {
                $this->connection->rollback();
            }

            throw $e;
        }

        $this->getEventManager()->trigger('match.completed', $match);

        return $match;
    }

    /**
     * Derives the winner from a set of set scores (whoever won more sets) and
     * records the result. Shared by both the admin and self-service (player)
     * result entry points, so the winner-derivation rule only lives in one place.
     *
     * @param TournamentMatch $match
     * @param array $setsData           Each item: ['games_a' => int, 'games_b' => int, 'is_match_tiebreak' => bool]
     * @return TournamentMatch
     * @throws RuntimeException        If the set scores don't produce a clear winner.
     */
    public function recordResultFromSets(TournamentMatch $match, array $setsData)
    {
        $setsWonA = 0;
        $setsWonB = 0;

        foreach ($setsData as $set) {
            if ($set['games_a'] > $set['games_b']) {
                $setsWonA++;
            } else if ($set['games_b'] > $set['games_a']) {
                $setsWonB++;
            }
        }

        if ($setsWonA == $setsWonB) {
            throw new RuntimeException('The entered set scores do not produce a clear winner');
        }

        $winnerTpid = $setsWonA > $setsWonB ? $match->need('player_a_tpid') : $match->need('player_b_tpid');

        return $this->recordResult($match, $setsData, $winnerTpid);
    }

    /**
     * Resets a match back to its pre-result state: removes its sets, clears
     * the winner and status, and — for a knockout match that already fed a
     * winner into the next round — also clears that slot in the next match,
     * so a stale advanced player isn't left behind.
     *
     * @param TournamentMatch $match
     * @return TournamentMatch
     */
    public function resetResult(TournamentMatch $match)
    {
        if (! $this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $transaction = true;
        } else {
            $transaction = false;
        }

        try {
            $this->matchSetManager->replaceSets($match, array());

            $match->set('winner_tpid', null);
            $match->set('status', 'scheduled');

            $this->matchManager->save($match);

            if ($match->need('phase') == 'knockout' && $match->get('feeds_into_tmaid')) {
                $nextMatch = $this->matchManager->get($match->need('feeds_into_tmaid'));

                $slotProperty = $match->need('feeds_into_slot') == 'A' ? 'player_a_tpid' : 'player_b_tpid';

                $nextMatch->set($slotProperty, null);

                $this->matchManager->save($nextMatch);
            }

            if ($transaction) {
                $this->connection->commit();
                $transaction = false;
            }
        } catch (Exception $e) {
            if ($transaction) {
                $this->connection->rollback();
            }

            throw $e;
        }

        $this->getEventManager()->trigger('match.reset', $match);

        return $match;
    }

    /**
     * Generates the 8-seed knockout bracket for a category from its qualifiers
     * (ordered seed 1..8, e.g. from QualificationService::selectQualifiers()).
     * Seeds are paired so that seed 1 and seed 2 can only meet in the final:
     * QF pairs (1,8) (4,5) (3,6) (2,7); QF1+QF2 winners feed SF1, QF3+QF4 feed SF2.
     * Refuses to run if a knockout bracket already exists for this category.
     *
     * @param TournamentCategory $category
     * @param array $qualifiers         Standings rows (each with a 'tpid' key), seed 1..8.
     * @return array                    [final, sf1, sf2, qf1, qf2, qf3, qf4]
     * @throws RuntimeException
     */
    public function generateBracket(TournamentCategory $category, array $qualifiers)
    {
        if ($this->matchManager->getByCategory($category, 'knockout')) {
            throw new RuntimeException('The knockout bracket has already been generated for this category');
        }

        if (count($qualifiers) != 8) {
            throw new RuntimeException('Exactly 8 qualifiers are required to generate the knockout bracket');
        }

        if (! $this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $transaction = true;
        } else {
            $transaction = false;
        }

        try {
            $tcid = $category->need('tcid');

            $final = new TournamentMatch(array(
                'tcid' => $tcid,
                'phase' => 'knockout',
                'round' => 'final',
                'bracket_slot' => 1,
                'status' => 'scheduled',
                'created' => date('Y-m-d H:i:s'),
            ));

            $this->matchManager->save($final);

            $sf1 = new TournamentMatch(array(
                'tcid' => $tcid,
                'phase' => 'knockout',
                'round' => 'sf',
                'bracket_slot' => 1,
                'feeds_into_tmaid' => $final->need('tmaid'),
                'feeds_into_slot' => 'A',
                'status' => 'scheduled',
                'created' => date('Y-m-d H:i:s'),
            ));

            $this->matchManager->save($sf1);

            $sf2 = new TournamentMatch(array(
                'tcid' => $tcid,
                'phase' => 'knockout',
                'round' => 'sf',
                'bracket_slot' => 2,
                'feeds_into_tmaid' => $final->need('tmaid'),
                'feeds_into_slot' => 'B',
                'status' => 'scheduled',
                'created' => date('Y-m-d H:i:s'),
            ));

            $this->matchManager->save($sf2);

            $qfPairs = array(
                array(1, 8, $sf1, 'A'),
                array(4, 5, $sf1, 'B'),
                array(3, 6, $sf2, 'A'),
                array(2, 7, $sf2, 'B'),
            );

            $qfMatches = array();

            foreach ($qfPairs as $index => $pairSpec) {
                list($seedA, $seedB, $feedsInto, $slot) = $pairSpec;

                $qf = new TournamentMatch(array(
                    'tcid' => $tcid,
                    'phase' => 'knockout',
                    'round' => 'qf',
                    'bracket_slot' => $index + 1,
                    'player_a_tpid' => $qualifiers[$seedA - 1]['tpid'],
                    'player_b_tpid' => $qualifiers[$seedB - 1]['tpid'],
                    'feeds_into_tmaid' => $feedsInto->need('tmaid'),
                    'feeds_into_slot' => $slot,
                    'status' => 'scheduled',
                    'created' => date('Y-m-d H:i:s'),
                ));

                $this->matchManager->save($qf);

                $qfMatches[] = $qf;
            }

            if ($transaction) {
                $this->connection->commit();
                $transaction = false;
            }
        } catch (Exception $e) {
            if ($transaction) {
                $this->connection->rollback();
            }

            throw $e;
        }

        $this->getEventManager()->trigger('bracket.generated', $category);

        return array_merge(array($final, $sf1, $sf2), $qfMatches);
    }

}
