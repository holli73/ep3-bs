<?php

namespace Backend\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class TournamentDrawController extends AbstractActionController
{

    public function indexAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $groupParticipantManager = $serviceManager->get('Tournament\Manager\TournamentGroupParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $drawService = $serviceManager->get('Tournament\Service\DrawService');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $groups = $drawService->initializeGroups($category);
        $assignments = $groupParticipantManager->getByGroups($groups);

        $participants = $participantManager->getByCategory($category, 'registered');

        $users = array();

        foreach ($participants as $participant) {
            $users[$participant->need('tpid')] = $userManager->get($participant->need('uid'), false);
        }

        $assignmentByTpid = array();

        foreach ($assignments as $assignment) {
            $assignmentByTpid[$assignment->need('tpid')] = $assignment;
        }

        $participantsByGroup = array();

        foreach ($groups as $group) {
            $participantsByGroup[$group->need('tgid')] = array();
        }

        $unassignedParticipants = array();

        foreach ($participants as $participant) {
            $tpid = $participant->need('tpid');

            if (isset($assignmentByTpid[$tpid])) {
                $tgid = $assignmentByTpid[$tpid]->need('tgid');

                if (isset($participantsByGroup[$tgid])) {
                    $participantsByGroup[$tgid][] = $participant;
                }
            } else {
                $unassignedParticipants[] = $participant;
            }
        }

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'groups' => $groups,
            'participantsByGroup' => $participantsByGroup,
            'unassignedParticipants' => $unassignedParticipants,
            'assignmentByTpid' => $assignmentByTpid,
            'users' => $users,
        );
    }

    public function assignAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $groupManager = $serviceManager->get('Tournament\Manager\TournamentGroupManager');
        $drawService = $serviceManager->get('Tournament\Service\DrawService');

        $tcid = $this->params()->fromRoute('tcid');

        if ($this->getRequest()->isPost()) {
            $tpid = $this->params()->fromPost('tpid');
            $tgid = $this->params()->fromPost('tgid');

            $participant = $participantManager->get($tpid);
            $group = $groupManager->get($tgid);

            $drawService->assignParticipant($participant, $group, true);

            $this->flashMessenger()->addSuccessMessage('Participant has been assigned');
        }

        return $this->redirect()->toRoute('backend/tournament/draw', array('tcid' => $tcid));
    }

    public function unassignAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $drawService = $serviceManager->get('Tournament\Service\DrawService');

        $tcid = $this->params()->fromRoute('tcid');

        if ($this->getRequest()->isPost()) {
            $tpid = $this->params()->fromPost('tpid');

            $participant = $participantManager->get($tpid);

            $drawService->unassignParticipant($participant);

            $this->flashMessenger()->addSuccessMessage('Participant has been unassigned');
        }

        return $this->redirect()->toRoute('backend/tournament/draw', array('tcid' => $tcid));
    }

    public function runAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $drawService = $serviceManager->get('Tournament\Service\DrawService');

        $tcid = $this->params()->fromRoute('tcid');

        if ($this->getRequest()->isPost()) {
            $category = $tournamentCategoryManager->get($tcid);

            try {
                $drawService->runDraw($category);

                $this->flashMessenger()->addSuccessMessage('The draw has been run');
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
        }

        return $this->redirect()->toRoute('backend/tournament/draw', array('tcid' => $tcid));
    }

}
