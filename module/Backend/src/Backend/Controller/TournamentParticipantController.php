<?php

namespace Backend\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class TournamentParticipantController extends AbstractActionController
{

    public function indexAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $participants = $participantManager->getByCategory($category);

        $users = array();

        foreach ($participants as $participant) {
            $users[$participant->need('tpid')] = $userManager->get($participant->need('uid'), false);
        }

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'participants' => $participants,
            'users' => $users,
        );
    }

    public function addAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $registrationService = $serviceManager->get('Tournament\Service\RegistrationService');
        $formElementManager = $serviceManager->get('FormElementManager');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);
        $tournament = $tournamentManager->get($category->need('tid'));

        $participantForm = $formElementManager->get('Backend\Form\Tournament\ParticipantForm');

        if ($this->getRequest()->isPost()) {
            $participantForm->setData($this->params()->fromPost());

            if ($participantForm->isValid()) {
                $data = $participantForm->getData();

                $identifier = $data['puf-user'];

                $user = null;

                if (preg_match('/\(([0-9]+)\)\s*$/', $identifier, $matches)) {
                    $user = $userManager->get($matches[1], false);
                }

                if (! $user) {
                    $users = $userManager->getBy(array('email' => $identifier));

                    if (! $users) {
                        $users = $userManager->getBy(array('alias' => $identifier));
                    }

                    $user = $users ? current($users) : null;
                }

                if (! $user) {
                    $this->flashMessenger()->addErrorMessage('No user found — start typing a name and pick one from the list');

                    return $this->redirect()->toRoute('backend/tournament/participants/add', array('tcid' => $tcid));
                }

                try {
                    $registrationService->registerByAdmin($user, $category);

                    $this->flashMessenger()->addSuccessMessage('Participant has been added');
                } catch (RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
                }

                return $this->redirect()->toRoute('backend/tournament/participants', array('tcid' => $tcid));
            }
        }

        return array(
            'tournament' => $tournament,
            'category' => $category,
            'participantForm' => $participantForm,
        );
    }

    public function removeAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $participantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $registrationService = $serviceManager->get('Tournament\Service\RegistrationService');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $tpid = $this->params()->fromRoute('tpid');

        $participant = $participantManager->get($tpid);
        $category = $tournamentCategoryManager->get($participant->need('tcid'));
        $user = $userManager->get($participant->need('uid'), false);

        if ($this->params()->fromQuery('confirmed') == 'true') {

            $registrationService->remove($participant);

            $this->flashMessenger()->addSuccessMessage('Participant has been removed');

            return $this->redirect()->toRoute('backend/tournament/participants', array('tcid' => $category->need('tcid')));
        }

        return array(
            'participant' => $participant,
            'category' => $category,
            'user' => $user,
        );
    }

}
