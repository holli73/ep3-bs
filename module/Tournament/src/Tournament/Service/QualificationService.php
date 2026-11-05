<?php

namespace Tournament\Service;

use RuntimeException;
use Tournament\Entity\TournamentCategory;

class QualificationService
{

    protected $standingsService;

    /**
     * Creates a new qualification service object.
     *
     * @param StandingsService $standingsService
     */
    public function __construct(StandingsService $standingsService)
    {
        $this->standingsService = $standingsService;
    }

    /**
     * Selects the qualifiers for the knockout phase via a rank-position merge:
     * every group's 1st-place finisher is ranked first (cross-group ties broken
     * by set/game differential, since head-to-head doesn't apply across groups),
     * then every group's 2nd-place finisher, and so on, until exactly $count
     * qualifiers are filled.
     *
     * @param TournamentCategory $category
     * @param int $count
     * @return array                        Standings rows, ordered seed 1..$count.
     * @throws RuntimeException
     */
    public function selectQualifiers(TournamentCategory $category, $count = 8)
    {
        $standingsByGroup = $this->standingsService->getCategoryStandings($category);

        $maxLen = 0;

        foreach ($standingsByGroup as $standings) {
            $maxLen = max($maxLen, count($standings));
        }

        $qualifiers = array();

        for ($tier = 0; $tier < $maxLen; $tier++) {
            $tierCandidates = array();

            foreach ($standingsByGroup as $standings) {
                if (isset($standings[$tier])) {
                    $tierCandidates[] = $standings[$tier];
                }
            }

            if (empty($tierCandidates)) {
                continue;
            }

            usort($tierCandidates, function ($a, $b) {
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

            $slotsLeft = $count - count($qualifiers);

            if (count($tierCandidates) <= $slotsLeft) {
                $qualifiers = array_merge($qualifiers, $tierCandidates);
            } else {
                $qualifiers = array_merge($qualifiers, array_slice($tierCandidates, 0, $slotsLeft));
                break;
            }

            if (count($qualifiers) >= $count) {
                break;
            }
        }

        if (count($qualifiers) < $count) {
            throw new RuntimeException(sprintf(
                'Not enough participants to select %d qualifiers (only %d available)',
                $count, count($qualifiers)));
        }

        return $qualifiers;
    }

}
