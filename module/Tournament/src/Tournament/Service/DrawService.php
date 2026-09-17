<?php

namespace Tournament\Service;

use Base\Service\AbstractService;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentGroup;
use Tournament\Entity\TournamentGroupParticipant;
use Tournament\Entity\TournamentMatch;
use Tournament\Entity\TournamentParticipant;
use Tournament\Manager\TournamentGroupManager;
use Tournament\Manager\TournamentGroupParticipantManager;
use Tournament\Manager\TournamentMatchManager;
use Tournament\Manager\TournamentParticipantManager;
use User\Manager\UserManager;
use Zend\Db\Adapter\Driver\ConnectionInterface;

class DrawService extends AbstractService
{

    protected $groupManager;
    protected $groupParticipantManager;
    protected $participantManager;
    protected $matchManager;
    protected $userManager;
    protected $connection;

    /**
     * Creates a new draw service object.
     *
     * @param TournamentGroupManager $groupManager
     * @param TournamentGroupParticipantManager $groupParticipantManager
     * @param TournamentParticipantManager $participantManager
     * @param TournamentMatchManager $matchManager
     * @param UserManager $userManager
     * @param ConnectionInterface $connection
     */
    public function __construct(
        TournamentGroupManager $groupManager,
        TournamentGroupParticipantManager $groupParticipantManager,
        TournamentParticipantManager $participantManager,
        TournamentMatchManager $matchManager,
        UserManager $userManager,
        ConnectionInterface $connection
    ) {
        $this->groupManager = $groupManager;
        $this->groupParticipantManager = $groupParticipantManager;
        $this->participantManager = $participantManager;
        $this->matchManager = $matchManager;
        $this->userManager = $userManager;
        $this->connection = $connection;
    }

    /**
     * Ensures enough groups exist for a category's current registered participant
     * count and group size, creating additional groups (on top of any that already
     * exist) if the current count falls short. Existing groups — and whoever is
     * already assigned to them — are never removed here; only new, empty groups
     * are added on top, since registration can keep growing between visits to the
     * draw page.
     *
     * @param TournamentCategory $category
     * @return array
     */
    public function initializeGroups(TournamentCategory $category)
    {
        $groups = $this->groupManager->getByCategory($category);

        $participantCount = count($this->participantManager->getByCategory($category, 'registered'));

        if ($participantCount == 0) {
            return $groups;
        }

        $groupSize = $category->get('group_size', 4);
        $desiredGroupCount = max(1, (int) ceil($participantCount / $groupSize));

        for ($i = count($groups); $i < $desiredGroupCount; $i++) {
            $group = new TournamentGroup(array(
                'tcid' => $category->need('tcid'),
                'label' => 'Group ' . chr(65 + ($i % 26)),
                'created' => date('Y-m-d H:i:s'),
            ));

            $this->groupManager->save($group);

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Assigns a participant to a group, either as a manual (locked) pin/move or
     * as part of the automated draw (unlocked). A locked assignment survives a
     * later re-run of the draw.
     *
     * @param TournamentParticipant $participant
     * @param TournamentGroup $group
     * @param boolean $locked
     * @return TournamentGroupParticipant
     */
    public function assignParticipant(TournamentParticipant $participant, TournamentGroup $group, $locked = true)
    {
        $assignment = $this->groupParticipantManager->getByParticipant($participant);

        if ($assignment) {
            $assignment->set('tgid', $group->need('tgid'));
            $assignment->set('locked', $locked ? 1 : 0);
        } else {
            $assignment = new TournamentGroupParticipant(array(
                'tgid' => $group->need('tgid'),
                'tpid' => $participant->need('tpid'),
                'locked' => $locked ? 1 : 0,
                'created' => date('Y-m-d H:i:s'),
            ));
        }

        $this->groupParticipantManager->save($assignment);

        return $assignment;
    }

    /**
     * Removes a participant from whichever group they are currently in.
     *
     * @param TournamentParticipant $participant
     */
    public function unassignParticipant(TournamentParticipant $participant)
    {
        $assignment = $this->groupParticipantManager->getByParticipant($participant);

        if ($assignment) {
            $this->groupParticipantManager->delete($assignment);
        }
    }

    /**
     * Runs (or re-runs) the automated draw for a category: distributes all
     * registered, non-locked participants across the category's groups, seeded
     * by ITN rating (best first, unrated last) in snake order across the groups,
     * randomized within same-rank tiers, while respecting group size balance
     * and any manually locked assignments (which are left untouched).
     *
     * @param TournamentCategory $category
     * @return array                        The groups, each carrying its participants as extra 'participants'.
     * @throws RuntimeException
     */
    public function runDraw(TournamentCategory $category)
    {
        $groups = $this->initializeGroups($category);

        if (empty($groups)) {
            throw new RuntimeException('There are no registered participants to draw');
        }

        $n = count($groups);

        $participants = $this->participantManager->getByCategory($category, 'registered');

        $lockedAssignments = $this->groupParticipantManager->getByGroups($groups, true);

        $lockedTpids = array();
        $lockedCountByGroup = array();

        foreach ($groups as $group) {
            $lockedCountByGroup[$group->need('tgid')] = 0;
        }

        foreach ($lockedAssignments as $assignment) {
            $lockedTpids[$assignment->need('tpid')] = true;

            $tgid = $assignment->need('tgid');

            if (isset($lockedCountByGroup[$tgid])) {
                $lockedCountByGroup[$tgid]++;
            }
        }

        $remaining = array();

        foreach ($participants as $participant) {
            if (! isset($lockedTpids[$participant->need('tpid')])) {
                $remaining[] = $participant;
            }
        }

        $totalCount = count($participants);
        $base = intdiv($totalCount, $n);
        $remainder = $totalCount % $n;

        $targetCapacity = array();
        $currentCount = array();

        foreach ($groups as $index => $group) {
            $tgid = $group->need('tgid');
            $fairTarget = $base + ($index < $remainder ? 1 : 0);

            $targetCapacity[$tgid] = max($fairTarget, $lockedCountByGroup[$tgid]);
            $currentCount[$tgid] = $lockedCountByGroup[$tgid];
        }

        /* Sort remaining participants by ITN rating, best (highest) first, unrated last */

        $itnByTpid = array();

        foreach ($remaining as $participant) {
            $user = $this->userManager->get($participant->need('uid'), false);

            $itnByTpid[$participant->need('tpid')] = $user ? $user->getItn() : null;
        }

        usort($remaining, function (TournamentParticipant $a, TournamentParticipant $b) use ($itnByTpid) {
            $itnA = $itnByTpid[$a->need('tpid')];
            $itnB = $itnByTpid[$b->need('tpid')];

            $aUnrated = is_null($itnA);
            $bUnrated = is_null($itnB);

            if ($aUnrated != $bUnrated) {
                return $aUnrated ? 1 : -1;
            }

            if (! $aUnrated && $itnA != $itnB) {
                return $itnA > $itnB ? -1 : 1;
            }

            return $a->need('tpid') <=> $b->need('tpid');
        });

        $tiers = array_chunk($remaining, $n);

        $assignmentsByGroup = array();

        foreach ($groups as $group) {
            $assignmentsByGroup[$group->need('tgid')] = array();
        }

        $forward = true;

        foreach ($tiers as $tier) {
            shuffle($tier);

            $orderedGroups = $forward ? $groups : array_reverse($groups);

            $queue = $tier;

            /* Cycle through the (ordered) groups as many times as needed until
             * every participant in this tier has been placed, skipping groups
             * that are already at their target capacity. */

            while (! empty($queue)) {
                $placedThisPass = false;

                foreach ($orderedGroups as $group) {
                    if (empty($queue)) {
                        break;
                    }

                    $tgid = $group->need('tgid');

                    if ($currentCount[$tgid] >= $targetCapacity[$tgid]) {
                        continue;
                    }

                    $participant = array_shift($queue);

                    $assignmentsByGroup[$tgid][] = $participant;
                    $currentCount[$tgid]++;

                    $placedThisPass = true;
                }

                if (! $placedThisPass) {
                    throw new RuntimeException('Could not place all participants into groups');
                }
            }

            $forward = ! $forward;
        }

        /* Persist: replace all non-locked assignments for this category's groups */

        if (! $this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $transaction = true;
        } else {
            $transaction = false;
        }

        try {
            $this->groupParticipantManager->deleteUnlockedByGroups($groups);

            foreach ($groups as $group) {
                $tgid = $group->need('tgid');

                foreach ($assignmentsByGroup[$tgid] as $participant) {
                    $this->groupParticipantManager->save(new TournamentGroupParticipant(array(
                        'tgid' => $tgid,
                        'tpid' => $participant->need('tpid'),
                        'locked' => 0,
                        'created' => date('Y-m-d H:i:s'),
                    )));
                }
            }

            if ($transaction) {
                $this->connection->commit();
                $transaction = false;
            }
        } catch (\Exception $e) {
            if ($transaction) {
                $this->connection->rollback();
            }

            throw $e;
        }

        $this->getEventManager()->trigger('draw.run', $category);

        return $groups;
    }

    /**
     * Generates the round-robin group-phase matches for a category, once its
     * groups have been finalized. For each group:
     *  - if its existing matches already cover exactly its current members
     *    (every pair, no one else), it's left untouched;
     *  - if membership has changed since matches were last generated (e.g. a
     *    player was moved or a group was added later as registration grew)
     *    and none of its existing matches have a recorded result yet, the
     *    stale matches are replaced with a fresh round-robin for the current
     *    members;
     *  - if membership changed but a result was already recorded, that group
     *    is skipped and flagged, since regenerating would silently discard it.
     *
     * @param TournamentCategory $category
     * @return array
     * @throws RuntimeException
     */
    public function generateRoundRobinMatches(TournamentCategory $category)
    {
        $groups = $this->groupManager->getByCategory($category);

        if (empty($groups)) {
            throw new RuntimeException('There are no groups to generate matches for');
        }

        $matches = array();
        $groupsWithRecordedResults = array();
        $anyChange = false;

        foreach ($groups as $group) {
            $assignments = $this->groupParticipantManager->getByGroup($group);

            $tpids = array();

            foreach ($assignments as $assignment) {
                $tpids[] = $assignment->need('tpid');
            }

            sort($tpids);

            $expectedPairs = array();

            $count = count($tpids);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $expectedPairs[] = $tpids[$i] . ':' . $tpids[$j];
                }
            }

            $existingMatches = $this->matchManager->getByGroup($group);

            $existingPairs = array();
            $hasRecordedResult = false;

            foreach ($existingMatches as $existingMatch) {
                $pair = array($existingMatch->get('player_a_tpid'), $existingMatch->get('player_b_tpid'));
                sort($pair);

                $existingPairs[] = $pair[0] . ':' . $pair[1];

                if ($existingMatch->need('status') != 'scheduled') {
                    $hasRecordedResult = true;
                }
            }

            sort($expectedPairs);
            sort($existingPairs);

            if ($expectedPairs === $existingPairs) {
                continue;
            }

            if ($hasRecordedResult) {
                $groupsWithRecordedResults[] = $group->need('label');
                continue;
            }

            $anyChange = true;

            foreach ($existingMatches as $existingMatch) {
                $this->matchManager->delete($existingMatch);
            }

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $match = new TournamentMatch(array(
                        'tcid' => $category->need('tcid'),
                        'phase' => 'group',
                        'tgid' => $group->need('tgid'),
                        'player_a_tpid' => $tpids[$i],
                        'player_b_tpid' => $tpids[$j],
                        'status' => 'scheduled',
                        'created' => date('Y-m-d H:i:s'),
                    ));

                    $this->matchManager->save($match);

                    $matches[] = $match;
                }
            }
        }

        if ($groupsWithRecordedResults) {
            throw new RuntimeException(sprintf(
                'Membership changed since matches were generated for %s, but %s already has recorded results — resolve this manually before regenerating',
                implode(', ', $groupsWithRecordedResults),
                count($groupsWithRecordedResults) == 1 ? 'it' : 'they'));
        }

        if (! $anyChange) {
            throw new RuntimeException('Group matches have already been generated for every group in this category');
        }

        $this->getEventManager()->trigger('fixtures.generated', $category);

        return $matches;
    }

}
