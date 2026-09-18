<?php

namespace Tournament\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class MatchController extends AbstractActionController
{

    public function resultAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $matchManager = $serviceManager->get('Tournament\Manager\TournamentMatchManager');
        $matchSetManager = $serviceManager->get('Tournament\Manager\TournamentMatchSetManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $categoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');
        $bracketService = $serviceManager->get('Tournament\Service\BracketService');
        $formElementManager = $serviceManager->get('FormElementManager');

        $tmaid = $this->params()->fromRoute('tmaid');

        $match = $matchManager->get($tmaid);
        $category = $categoryManager->get($match->need('tcid'));

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('tournament/match-result', array('tmaid' => $tmaid));

            return $this->redirect()->toRoute('user/login');
        }

        $playerA = $match->get('player_a_tpid') ? $participantManager->get($match->need('player_a_tpid'), false) : null;
        $playerB = $match->get('player_b_tpid') ? $participantManager->get($match->need('player_b_tpid'), false) : null;

        $isOwnMatch = ($playerA && $playerA->need('uid') == $user->need('uid'))
            || ($playerB && $playerB->need('uid') == $user->need('uid'));

        if (! $isOwnMatch) {
            throw new RuntimeException('You can only enter the result of your own matches');
        }

        if (! ($playerA && $playerB)) {
            throw new RuntimeException('This match does not have both players determined yet');
        }

        $userA = $userManager->get($playerA->need('uid'), false);
        $userB = $userManager->get($playerB->need('uid'), false);

        $resultForm = $formElementManager->get('Tournament\Form\MatchResultForm');

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

                    return $this->redirect()->toRoute('tournament/view', array('tid' => $category->need('tid')));
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
            'category' => $category,
            'userA' => $userA,
            'userB' => $userB,
            'resultForm' => $resultForm,
        );
    }

}
