<?php

namespace App\Security\Voter;

use App\Entity\Consultation;
use App\Entity\Doctor;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class ConsultationVoter extends Voter
{
    // These strings are used in the template and controller
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
        // Only vote on Consultation objects and specific attributes
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // Only vote on Consultation objects
        if (!$subject instanceof Consultation) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // The user must be logged in; if not, deny access
        if (!$user instanceof Doctor) {
            return false;
        }

        /** @var Consultation $consultation */
        $consultation = $subject;

        // Check if the consultation belongs to the current doctor
        return $consultation->getDoctor() === $user;
    }
}
