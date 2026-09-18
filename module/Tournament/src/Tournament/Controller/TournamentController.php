<?php

namespace Tournament\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class TournamentController extends AbstractActionController
{

    public function indexAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');

        $tournaments = $tournamentManager->getAll('date_start DESC', 50);

        return array(
            'tournaments' => $tournaments,
        );
    }

    public function viewAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentParticipantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $tournamentMatchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $tournamentMatchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $tid = $this->params()->fromRoute('tid');

        $tournament = $tournamentManager->get($tid);
        $categories = $tournamentCategoryManager->getByTournament($tournament);

        $user = $userSessionManager->getSessionUser();

        $participantCounts = array();
        $ownParticipants = array();
        $ownMatches = array();
        $matchUsers = array();
        $matchScores = array();

        foreach ($categories as $gender => $category) {
            $participantCounts[$gender] = count($tournamentParticipantManager->getByCategory($category, 'registered'));

            if (! $user) {
                continue;
            }

            $ownParticipant = $tournamentParticipantManager->getByCategoryAndUser($category, $user->need('uid'));
            $ownParticipants[$gender] = $ownParticipant;

            if (! ($ownParticipant && $ownParticipant->need('status') == 'registered')) {
                continue;
            }

            $matches = $tournamentMatchManager->getByParticipant($ownParticipant);
            $ownMatches[$gender] = $matches;

            foreach ($matches as $match) {
                foreach (array('player_a_tpid', 'player_b_tpid') as $property) {
                    $tpid = $match->get($property);

                    if ($tpid && ! isset($matchUsers[$tpid])) {
                        $participant = $tournamentParticipantManager->get($tpid, false);

                        if ($participant) {
                            $matchUsers[$tpid] = $userManager->get($participant->need('uid'), false);
                        }
                    }
                }
            }

            $matchScores = array_merge($matchScores, $tournamentMatchSetManager->getScoreStringsByMatches($matches));
        }

        return array(
            'tournament' => $tournament,
            'categories' => $categories,
            'participantCounts' => $participantCounts,
            'ownParticipants' => $ownParticipants,
            'ownMatches' => $ownMatches,
            'matchUsers' => $matchUsers,
            'matchScores' => $matchScores,
            'user' => $user,
        );
    }

    public function standingsAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentGroupManager = $serviceManager->get('Tournament\Manager\TournamentGroupManager');
        $tournamentMatchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $tournamentMatchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $standingsService = $serviceManager->get('Tournament\Service\StandingsService');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $groups = $tournamentGroupManager->getByCategory($category);
        $standingsByGroup = $standingsService->getCategoryStandings($category);

        $users = array();

        foreach ($standingsByGroup as $standings) {
            foreach ($standings as $row) {
                $tpid = $row['tpid'];

                if (! isset($users[$tpid])) {
                    $users[$tpid] = $userManager->get($row['participant']->need('uid'), false);
                }
            }
        }

        $playedMatchesByGroup = array();
        $matchScores = array();

        foreach ($groups as $group) {
            $matches = $tournamentMatchManager->getByGroup($group);

            $playedMatches = array_values(array_filter($matches, function ($match) {
                return $match->need('status') == 'completed';
            }));

            $playedMatchesByGroup[$group->need('tgid')] = $playedMatches;

            $matchScores = array_merge($matchScores, $tournamentMatchSetManager->getScoreStringsByMatches($playedMatches));
        }

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'groups' => $groups,
            'standingsByGroup' => $standingsByGroup,
            'users' => $users,
            'playedMatchesByGroup' => $playedMatchesByGroup,
            'matchScores' => $matchScores,
        );
    }

    public function bracketAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $matchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $matches = $matchManager->getByCategory($category, 'knockout');

        $matchesByRound = array('qf' => array(), 'sf' => array(), 'final' => array());
        $users = array();

        foreach ($matches as $match) {
            $round = $match->get('round');

            if (isset($matchesByRound[$round])) {
                $matchesByRound[$round][] = $match;
            }

            foreach (array('player_a_tpid', 'player_b_tpid', 'winner_tpid') as $property) {
                $tpid = $match->get($property);

                if ($tpid && ! isset($users[$tpid])) {
                    $participant = $participantManager->get($tpid, false);

                    if ($participant) {
                        $users[$tpid] = $userManager->get($participant->need('uid'), false);
                    }
                }
            }
        }

        foreach ($matchesByRound as &$roundMatches) {
            usort($roundMatches, function ($a, $b) {
                return $a->get('bracket_slot') - $b->get('bracket_slot');
            });
        }

        $matchScores = $matchSetManager->getScoreStringsByMatches($matches);

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'matchesByRound' => $matchesByRound,
            'users' => $users,
            'matchScores' => $matchScores,
        );
    }

}
