<?php

namespace Backend\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class TournamentMatchController extends AbstractActionController
{

    public function indexAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentGroupManager = $serviceManager->get('Tournament\Manager\TournamentGroupManager');
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $matchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $groups = $tournamentGroupManager->getByCategory($category);

        $matchesByGroup = array();
        $users = array();
        $scoresByMatch = array();

        foreach ($groups as $group) {
            $matches = $matchManager->getByGroup($group);
            $matchesByGroup[$group->need('tgid')] = $matches;

            foreach ($matches as $match) {
                foreach (array('player_a_tpid', 'player_b_tpid') as $property) {
                    $tpid = $match->get($property);

                    if ($tpid && ! isset($users[$tpid])) {
                        $participant = $participantManager->get($tpid, false);

                        if ($participant) {
                            $users[$tpid] = $userManager->get($participant->need('uid'), false);
                        }
                    }
                }
            }

            $scoresByMatch = $scoresByMatch + ($matchSetManager->getScoreStringsByMatches($matches));
        }

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'groups' => $groups,
            'matchesByGroup' => $matchesByGroup,
            'users' => $users,
            'scoresByMatch' => $scoresByMatch,
        );
    }

    public function generateAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $drawService = $serviceManager->get('Tournament\Service\DrawService');

        $tcid = $this->params()->fromRoute('tcid');

        if ($this->getRequest()->isPost()) {
            $category = $tournamentCategoryManager->get($tcid);

            try {
                $drawService->generateRoundRobinMatches($category);

                $this->flashMessenger()->addSuccessMessage('The group matches have been generated');
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
        }

        return $this->redirect()->toRoute('backend/tournament/matches', array('tcid' => $tcid));
    }

    public function generateKnockoutAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $qualificationService = $serviceManager->get('Tournament\Service\QualificationService');
        $bracketService = $serviceManager->get('Tournament\Service\BracketService');

        $tcid = $this->params()->fromRoute('tcid');

        if ($this->getRequest()->isPost()) {
            $category = $tournamentCategoryManager->get($tcid);

            try {
                $qualifiers = $qualificationService->selectQualifiers($category, 8);

                $bracketService->generateBracket($category, $qualifiers);

                $this->flashMessenger()->addSuccessMessage('The knockout bracket has been generated');
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
        }

        return $this->redirect()->toRoute('backend/tournament/knockout', array('tcid' => $tcid));
    }

    public function knockoutAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
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

        $scoresByMatch = $matchSetManager->getScoreStringsByMatches($matches);

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'matchesByRound' => $matchesByRound,
            'users' => $users,
            'scoresByMatch' => $scoresByMatch,
        );
    }

    public function resetAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $bracketService = $serviceManager->get('Tournament\Service\BracketService');

        $tmaid = $this->params()->fromRoute('tmaid');

        $match = $matchManager->get($tmaid);

        if ($this->params()->fromQuery('confirmed') == 'true') {
            $bracketService->resetResult($match);

            $this->flashMessenger()->addSuccessMessage('The result has been reset');

            if ($match->need('phase') == 'knockout') {
                return $this->redirect()->toRoute('backend/tournament/knockout', array('tcid' => $match->need('tcid')));
            }

            return $this->redirect()->toRoute('backend/tournament/matches', array('tcid' => $match->need('tcid')));
        }

        $playerA = $participantManager->get($match->need('player_a_tpid'), false);
        $playerB = $participantManager->get($match->need('player_b_tpid'), false);

        $userA = $playerA ? $userManager->get($playerA->need('uid'), false) : null;
        $userB = $playerB ? $userManager->get($playerB->need('uid'), false) : null;

        return array(
            'match' => $match,
            'userA' => $userA,
            'userB' => $userB,
        );
    }

    public function resultAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $matchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $bracketService = $serviceManager->get('Tournament\Service\BracketService');
        $formElementManager = $serviceManager->get('FormElementManager');

        $tmaid = $this->params()->fromRoute('tmaid');

        $match = $matchManager->get($tmaid);

        $playerA = $participantManager->get($match->need('player_a_tpid'), false);
        $playerB = $participantManager->get($match->need('player_b_tpid'), false);

        $userA = $playerA ? $userManager->get($playerA->need('uid'), false) : null;
        $userB = $playerB ? $userManager->get($playerB->need('uid'), false) : null;

        $resultForm = $formElementManager->get('Backend\Form\Tournament\MatchResultForm');

        if ($this->getRequest()->isPost()) {
            $resultForm->setData($this->params()->fromPost());

            if ($resultForm->isValid()) {
                $data = $resultForm->getData();

                $setsData = array();

                for ($i = 1; $i <= 3; $i++) {
                    $gamesA = $data["mrf-set{$i}-a"];
                    $gamesB = $data["mrf-set{$i}-b"];

                    if ($gamesA === '' || $gamesA === null || $gamesB === '' || $gamesB === null) {
                        continue;
                    }

                    $setsData[] = array(
                        'games_a' => (int) $gamesA,
                        'games_b' => (int) $gamesB,
                        'is_match_tiebreak' => $i == 3 && $this->params()->fromPost('mrf-set3-tiebreak'),
                    );
                }

                try {
                    $bracketService->recordResultFromSets($match, $setsData);

                    $this->flashMessenger()->addSuccessMessage('The result has been saved');

                    if ($match->need('phase') == 'knockout') {
                        return $this->redirect()->toRoute('backend/tournament/knockout', array('tcid' => $match->need('tcid')));
                    }

                    return $this->redirect()->toRoute('backend/tournament/matches', array('tcid' => $match->need('tcid')));
                } catch (RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
                }
            }
        } else {
            $existingSets = $matchSetManager->getByMatch($match);

            $setData = array();

            foreach ($existingSets as $set) {
                $setNumber = $set->need('set_number');

                $setData["mrf-set{$setNumber}-a"] = $set->need('games_a');
                $setData["mrf-set{$setNumber}-b"] = $set->need('games_b');

                if ($set->get('is_match_tiebreak')) {
                    $setData['mrf-set3-tiebreak'] = '1';
                }
            }

            $resultForm->setData($setData);
        }

        return array(
            'match' => $match,
            'userA' => $userA,
            'userB' => $userB,
            'resultForm' => $resultForm,
        );
    }

}
