<?php

namespace Tournament\Service;

use Base\Service\AbstractService;
use DateTime;
use RuntimeException;
use Tournament\Entity\TournamentCategory;
use Tournament\Entity\TournamentParticipant;
use Tournament\Manager\TournamentParticipantManager;
use User\Entity\User;

class RegistrationService extends AbstractService
{

    protected $participantManager;

    /**
     * Creates a new registration service object.
     *
     * @param TournamentParticipantManager $participantManager
     */
    public function __construct(TournamentParticipantManager $participantManager)
    {
        $this->participantManager = $participantManager;
    }

    /**
     * Registers a user for a category through the public, self-service flow.
     *
     * Enforces the registration deadline and that the category's gender matches the
     * user's profile gender. Reactivates a previously withdrawn registration instead
     * of inserting a duplicate row, since (tcid, uid) is unique.
     *
     * @param User $user
     * @param TournamentCategory $category
     * @return TournamentParticipant
     * @throws RuntimeException
     */
    public function registerSelf(User $user, TournamentCategory $category)
    {
        if ($category->need('status') != 'registration-open') {
            throw new RuntimeException('Registration is not open for this category');
        }

        $deadline = $category->get('registration_deadline');

        if ($deadline && new DateTime() > new DateTime($deadline)) {
            throw new RuntimeException('The registration deadline has passed');
        }

        if ($user->getMeta('gender') != $category->need('gender')) {
            throw new RuntimeException('This category is not open to your gender');
        }

        $participant = $this->participantManager->getByCategoryAndUser($category, $user->need('uid'));

        if ($participant) {
            if ($participant->need('status') == 'registered') {
                throw new RuntimeException('You are already registered for this category');
            }

            if ($participant->need('status') == 'disqualified') {
                throw new RuntimeException('You cannot register for this category');
            }

            $participant->set('status', 'registered');
            $participant->set('registered_via', 'self');
        } else {
            $participant = new TournamentParticipant(array(
                'tcid' => $category->need('tcid'),
                'uid' => $user->need('uid'),
                'status' => 'registered',
                'registered_via' => 'self',
            ));
        }

        $this->participantManager->save($participant);

        $this->getEventManager()->trigger('register.self', $participant);

        return $participant;
    }

    /**
     * Registers a user for a category through the admin flow.
     *
     * Bypasses the deadline and gender checks, for walk-ins and corrections.
     *
     * @param User $user
     * @param TournamentCategory $category
     * @return TournamentParticipant
     * @throws RuntimeException
     */
    public function registerByAdmin(User $user, TournamentCategory $category)
    {
        $participant = $this->participantManager->getByCategoryAndUser($category, $user->need('uid'));

        if ($participant) {
            if ($participant->need('status') == 'registered') {
                throw new RuntimeException('This user is already registered for this category');
            }

            $participant->set('status', 'registered');
            $participant->set('registered_via', 'admin');
        } else {
            $participant = new TournamentParticipant(array(
                'tcid' => $category->need('tcid'),
                'uid' => $user->need('uid'),
                'status' => 'registered',
                'registered_via' => 'admin',
            ));
        }

        $this->participantManager->save($participant);

        $this->getEventManager()->trigger('register.admin', $participant);

        return $participant;
    }

    /**
     * Withdraws a participant's own registration (self-service).
     *
     * Keeps the row (as 'withdrawn') rather than deleting it, so a later re-registration
     * reactivates it instead of violating the (tcid, uid) unique constraint.
     *
     * @param TournamentParticipant $participant
     * @return TournamentParticipant
     */
    public function withdraw(TournamentParticipant $participant)
    {
        $participant->set('status', 'withdrawn');

        $this->participantManager->save($participant);

        $this->getEventManager()->trigger('withdraw', $participant);

        return $participant;
    }

    /**
     * Removes a participant entirely (admin correction).
     *
     * @param TournamentParticipant $participant
     */
    public function remove(TournamentParticipant $participant)
    {
        $this->participantManager->delete($participant);

        $this->getEventManager()->trigger('remove', $participant);
    }

}
