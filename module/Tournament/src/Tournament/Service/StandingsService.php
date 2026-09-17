<?php

namespace Tournament\Service;

use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentGroup;
use Tournament\Manager\TournamentGroupManager;
use Tournament\Manager\TournamentGroupParticipantManager;
use Tournament\Manager\TournamentMatchManager;
use Tournament\Manager\TournamentMatchSetManager;
use Tournament\Manager\TournamentParticipantManager;

class StandingsService
{

    protected $groupManager;
    protected $groupParticipantManager;
    protected $matchManager;
    protected $matchSetManager;
    protected $participantManager;

    /**
     * Creates a new standings service object.
     *
     * @param TournamentGroupManager $groupManager
     * @param TournamentGroupParticipantManager $groupParticipantManager
     * @param TournamentMatchManager $matchManager
     * @param TournamentMatchSetManager $matchSetManager
     * @param TournamentParticipantManager $participantManager
     */
    public function __construct(
        TournamentGroupManager $groupManager,
        TournamentGroupParticipantManager $groupParticipantManager,
        TournamentMatchManager $matchManager,
        TournamentMatchSetManager $matchSetManager,
        TournamentParticipantManager $participantManager
    ) {
        $this->groupManager = $groupManager;
        $this->groupParticipantManager = $groupParticipantManager;
        $this->matchManager = $matchManager;
        $this->matchSetManager = $matchSetManager;
        $this->participantManager = $participantManager;
    }

    /**
     * Computes the standings of a single group, ranked by: match wins, then
     * head-to-head (decisive whenever the two rows being compared actually
     * played each other), then set difference, then game difference, then a
     * stable fallback (participant id) so the ranking never changes on recompute.
     *
     * @param TournamentGroup $group
     * @return array                Each row: tpid, participant, wins, losses,
     *                               setsWon, setsLost, gamesWon, gamesLost, rank.
     */
    public function getGroupStandings(TournamentGroup $group)
    {
        $assignments = $this->groupParticipantManager->getByGroup($group);

        $stats = array();

        foreach ($assignments as $assignment) {
            $tpid = $assignment->need('tpid');

            $stats[$tpid] = array(
                'tpid' => $tpid,
                'wins' => 0,
                'losses' => 0,
                'setsWon' => 0,
                'setsLost' => 0,
                'gamesWon' => 0,
                'gamesLost' => 0,
                'headToHead' => array(),
            );
        }

        $matches = $this->matchManager->getByGroup($group);

        foreach ($matches as $match) {
            if ($match->need('status') != 'completed') {
                continue;
            }

            $a = $match->get('player_a_tpid');
            $b = $match->get('player_b_tpid');
            $winner = $match->get('winner_tpid');

            if (! (isset($stats[$a]) && isset($stats[$b]))) {
                continue;
            }

            $sets = $this->matchSetManager->getByMatch($match);

            $gamesA = 0;
            $gamesB = 0;
            $setsWonA = 0;
            $setsWonB = 0;

            foreach ($sets as $set) {
                $gA = $set->need('games_a');
                $gB = $set->need('games_b');

                $gamesA += $gA;
                $gamesB += $gB;

                if ($gA > $gB) {
                    $setsWonA++;
                } else if ($gB > $gA) {
                    $setsWonB++;
                }
            }

            $stats[$a]['gamesWon'] += $gamesA;
            $stats[$a]['gamesLost'] += $gamesB;
            $stats[$a]['setsWon'] += $setsWonA;
            $stats[$a]['setsLost'] += $setsWonB;

            $stats[$b]['gamesWon'] += $gamesB;
            $stats[$b]['gamesLost'] += $gamesA;
            $stats[$b]['setsWon'] += $setsWonB;
            $stats[$b]['setsLost'] += $setsWonA;

            if ($winner == $a) {
                $stats[$a]['wins']++;
                $stats[$b]['losses']++;
                $stats[$a]['headToHead'][$b] = 'win';
                $stats[$b]['headToHead'][$a] = 'loss';
            } else if ($winner == $b) {
                $stats[$b]['wins']++;
                $stats[$a]['losses']++;
                $stats[$b]['headToHead'][$a] = 'win';
                $stats[$a]['headToHead'][$b] = 'loss';
            }
        }

        $standings = array_values($stats);

        usort($standings, function ($a, $b) {
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] - $a['wins'];
            }

            if (isset($a['headToHead'][$b['tpid']])) {
                return $a['headToHead'][$b['tpid']] == 'win' ? -1 : 1;
            }

            $setDiffA = $a['setsWon'] - $a['setsLost'];
            $setDiffB = $b['setsWon'] - $b['setsLost'];

            if ($setDiffA != $setDiffB) {
                return $setDiffB - $setDiffA;
            }

            $gameDiffA = $a['gamesWon'] - $a['gamesLost'];
            $gameDiffB = $b['gamesWon'] - $b['gamesLost'];

            if ($gameDiffA != $gameDiffB) {
                return $gameDiffB - $gameDiffA;
            }

            return $a['tpid'] - $b['tpid'];
        });

        foreach ($standings as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['participant'] = $this->participantManager->get($row['tpid']);
        }

        return $standings;
    }

    /**
     * Computes standings for every group of a category.
     *
     * @param TournamentCategory $category
     * @return array                       [tgid => standings[]]
     */
    public function getCategoryStandings(TournamentCategory $category)
    {
        $groups = $this->groupManager->getByCategory($category);

        $standings = array();

        foreach ($groups as $group) {
            $standings[$group->need('tgid')] = $this->getGroupStandings($group);
        }

        return $standings;
    }

}
