<?php

namespace App\DataFixtures;

use App\Entity\Patient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PatientFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $patient = new Patient();
        $patient->setEmail('patient@test.com');
        $patient->setFirstName('John');
        $patient->setLastName('Doe');
        $patient->setRoles(['ROLE_PATIENT']);
        $patient->setBirthDate(new \DateTime('1990-01-01'));
        $patient->setBloodType('A+');
        $patient->setAllergies('None');

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'password123');
        $patient->setPassword($hashedPassword);

        $manager->persist($patient);
        $manager->flush();
    }
} 