<?php

namespace App\Security\Voter;

use App\Entity\Ordonnance;
use App\Entity\User;
use App\Entity\Patient;
use App\Entity\Doctor;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class OrdonnanceVoter extends Voter
{
    // Actions
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // If the attribute isn't one we support, return false
        if (!\in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // Only vote on Ordonnance objects
        if (!$subject instanceof Ordonnance) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // The user must be logged in; if not, deny access
        if (!$user instanceof User) {
            return false;
        }

        // Admins can do anything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Ordonnance $ordonnance */
        $ordonnance = $subject;
        $consultation = $ordonnance->getConsultation();

        // Check if the consultation exists
        if (!$consultation) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($ordonnance, $user);
            case self::EDIT:
                return $this->canEdit($ordonnance, $user);
            case self::DELETE:
                return $this->canDelete($ordonnance, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Ordonnance $ordonnance, User $user): bool
    {
        $consultation = $ordonnance->getConsultation();
        
        // Patient can view their own ordonnances
        if ($user instanceof Patient && $consultation->getPatient() === $user) {
            return true;
        }
        
        // Doctor can view ordonnances they created
        if ($user instanceof Doctor && $consultation->getDoctor() === $user) {
            return true;
        }
        
        return false;
    }

    private function canEdit(Ordonnance $ordonnance, User $user): bool
    {
        // Only the doctor who created the consultation can edit the ordonnance
        return $user instanceof Doctor && 
               $ordonnance->getConsultation()->getDoctor() === $user;
    }

    private function canDelete(Ordonnance $ordonnance, User $user): bool
    {
        // Only the doctor who created the consultation can delete the ordonnance
        return $this->canEdit($ordonnance, $user);
    }
}
