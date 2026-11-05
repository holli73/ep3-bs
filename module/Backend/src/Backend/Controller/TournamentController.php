<?php

namespace Backend\Controller;

use DateTime;
use IntlDateFormatter;
use Tournament\Entity\Tournament;
use Zend\Mvc\Controller\AbstractActionController;

class TournamentController extends AbstractActionController
{

    public function indexAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');

        $tournaments = $tournamentManager->getAll('date_start DESC', 50);

        $this->redirectBack()->setOrigin('backend/tournament');

        return array(
            'tournaments' => $tournaments,
        );
    }

    public function editAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');
        $tournamentCategoryManager = $serviceManager->get('Tournament\Manager\TournamentCategoryManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $tid = $this->params()->fromRoute('tid');

        if ($tid) {
            $tournament = $tournamentManager->get($tid);
            $categories = $tournamentCategoryManager->ensureBothGenders($tournament);
        } else {
            $tournament = null;
            $categories = array();
        }

        $editForm = $formElementManager->get('Backend\Form\Tournament\EditForm');

        if ($this->getRequest()->isPost()) {
            $editForm->setData($this->params()->fromPost());

            if ($editForm->isValid()) {
                $data = $editForm->getData();

                if (! $tournament) {
                    $tournament = new Tournament();
                }

                $locale = $this->config('i18n.locale');

                $tournament->setMeta('name', $data['tf-name'], $locale);
                $tournament->setMeta('description', $data['tf-description'], $locale);

                $tournament->set('date_start', (new DateTime($data['tf-date-start']))->format('Y-m-d'));
                $tournament->set('date_end', (new DateTime($data['tf-date-end']))->format('Y-m-d'));

                if ($data['tf-registration-deadline']) {
                    $deadline = new DateTime($data['tf-registration-deadline']);
                    $deadline->setTime(23, 59, 59);

                    $tournament->set('registration_deadline', $deadline->format('Y-m-d H:i:s'));
                } else {
                    $tournament->set('registration_deadline', null);
                }

                $tournament->set('status', $data['tf-status']);

                $tournamentManager->save($tournament);

                $categories = $tournamentCategoryManager->ensureBothGenders($tournament);

                $categories['male']->set('group_size', $data['tf-male-group-size']);
                $categories['male']->set('status', $data['tf-status']);
                $tournamentCategoryManager->save($categories['male']);

                $categories['female']->set('group_size', $data['tf-female-group-size']);
                $categories['female']->set('status', $data['tf-status']);
                $tournamentCategoryManager->save($categories['female']);

                $this->flashMessenger()->addSuccessMessage('Tournament has been saved');

                return $this->redirectBack()->toOrigin();
            }
        } else {
            if ($tournament) {
                $editForm->setData(array(
                    'tf-name' => $tournament->getMeta('name'),
                    'tf-description' => $tournament->getMeta('description'),
                    'tf-date-start' => $this->dateFormat(new DateTime($tournament->need('date_start')), IntlDateFormatter::MEDIUM),
                    'tf-date-end' => $this->dateFormat(new DateTime($tournament->need('date_end')), IntlDateFormatter::MEDIUM),
                    'tf-registration-deadline' => $tournament->get('registration_deadline')
                        ? $this->dateFormat(new DateTime($tournament->need('registration_deadline')), IntlDateFormatter::MEDIUM)
                        : null,
                    'tf-status' => $tournament->get('status'),
                    'tf-male-group-size' => $categories['male']->get('group_size', 4),
                    'tf-female-group-size' => $categories['female']->get('group_size', 4),
                ));
            } else {
                $editForm->setData(array(
                    'tf-status' => 'draft',
                    'tf-male-group-size' => 4,
                    'tf-female-group-size' => 4,
                ));
            }
        }

        return array(
            'tournament' => $tournament,
            'categories' => $categories,
            'editForm' => $editForm,
        );
    }

    public function deleteAction()
    {
        $this->authorize('admin.tournament');

        $serviceManager = @$this->getServiceLocator();
        $tournamentManager = $serviceManager->get('Tournament\Manager\TournamentManager');

        $tid = $this->params()->fromRoute('tid');

        $tournament = $tournamentManager->get($tid);

        if ($this->params()->fromQuery('confirmed') == 'true') {

            $tournamentManager->delete($tournament);

            $this->flashMessenger()->addSuccessMessage('Tournament has been deleted');

            return $this->redirectBack()->toOrigin();
        }

        return array(
            'tournament' => $tournament,
        );
    }

}
