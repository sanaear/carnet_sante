<?php

namespace App\DataFixtures;

use App\Entity\Patient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create a test patient
        $patient = new Patient();
        $patient->setEmail('test.patient@example.com');
        $patient->setFirstName('Test');
        $patient->setLastName('Patient');
        $patient->setPhone('0123456789');
        
        // Hash a test password
        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'test123');
        $patient->setPassword($hashedPassword);

        $manager->persist($patient);
        $manager->flush();
    }
} 