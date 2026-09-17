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
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $tid = $this->params()->fromRoute('tid');

        $tournament = $tournamentManager->get($tid);
        $categories = $tournamentCategoryManager->getByTournament($tournament);

        $user = $userSessionManager->getSessionUser();

        $participantCounts = array();
        $ownParticipants = array();

        foreach ($categories as $gender => $category) {
            $participantCounts[$gender] = count($tournamentParticipantManager->getByCategory($category, 'registered'));

            if ($user) {
                $ownParticipants[$gender] = $tournamentParticipantManager->getByCategoryAndUser($category, $user->need('uid'));
            }
        }

        return array(
            'tournament' => $tournament,
            'categories' => $categories,
            'participantCounts' => $participantCounts,
            'ownParticipants' => $ownParticipants,
            'user' => $user,
        );
    }

    public function standingsAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentGroupManager = $serviceManager->get('Tournament\Manager\TournamentGroupManager');
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

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'groups' => $groups,
            'standingsByGroup' => $standingsByGroup,
            'users' => $users,
        );
    }

    public function bracketAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
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

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'matchesByRound' => $matchesByRound,
            'users' => $users,
        );
    }

}
