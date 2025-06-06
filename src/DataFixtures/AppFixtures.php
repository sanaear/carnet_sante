<?php

namespace App\DataFixtures;

use App\Entity\Patient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create a patient user
        $patient = new Patient();
        $patient->setEmail('patient@example.com');
        $patient->setNomComplet('Patient Test');
        $patient->setPhone('0123456789');
        $patient->setDateNaissance(new \DateTime('1990-01-01'));
        
        // Set a simple password: 'password123'
        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'password123');
        $patient->setPassword($hashedPassword);

        $manager->persist($patient);
        $manager->flush();
    }
}
