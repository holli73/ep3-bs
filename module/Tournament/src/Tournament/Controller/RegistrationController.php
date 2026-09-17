<?php

namespace Tournament\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class RegistrationController extends AbstractActionController
{

    public function registerAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $registrationService = $serviceManager->get('Tournament\Service\RegistrationService');
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $tcid = $this->params()->fromRoute('tcid');

        $category = $tournamentCategoryManager->get($tcid);

        $this->redirectBack()->setOrigin('tournament/view', array('tid' => $category->need('tid')));

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            return $this->redirect()->toRoute('user/login');
        }

        if ($this->getRequest()->isPost()) {
            try {
                $registrationService->registerSelf($user, $category);

                $this->flashMessenger()->addSuccessMessage('You have been registered');
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
        }

        return $this->redirect()->toRoute('tournament/view', array('tid' => $category->need('tid')));
    }

    public function withdrawAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $tournamentParticipantManager = $serviceManager->get('Tournament\Manager\TournamentParticipantManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $registrationService = $serviceManager->get('Tournament\Service\RegistrationService');
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $tpid = $this->params()->fromRoute('tpid');

        $participant = $tournamentParticipantManager->get($tpid);
        $category = $tournamentCategoryManager->get($participant->need('tcid'));

        $this->redirectBack()->setOrigin('tournament/view', array('tid' => $category->need('tid')));

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            return $this->redirect()->toRoute('user/login');
        }

        if ($participant->need('uid') != $user->need('uid')) {
            throw new RuntimeException('You cannot withdraw someone else\'s registration');
        }

        if ($this->getRequest()->isPost()) {
            $registrationService->withdraw($participant);

            $this->flashMessenger()->addSuccessMessage('You have been withdrawn');
        }

        return $this->redirect()->toRoute('tournament/view', array('tid' => $category->need('tid')));
    }

}
