<?php

namespace App\Command;

use App\Entity\Administrator;
use App\Entity\Doctor;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates test users for all roles'
)]
class CreateTestUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create Admin user
        $admin = new Administrator();
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPhone('1234567890');
        $admin->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        
        $this->entityManager->persist($admin);
        $output->writeln('Created admin user: admin@example.com / admin123');

        // Create Doctor user
        $doctor = new Doctor();
        $doctor->setEmail('doctor@example.com');
        $doctor->setFirstName('John');
        $doctor->setLastName('Doe');
        $doctor->setPhone('0987654321');
        $doctor->setSpeciality('Cardiology');
        
        $hashedPassword = $this->passwordHasher->hashPassword($doctor, 'doctor123');
        $doctor->setPassword($hashedPassword);
        
        $this->entityManager->persist($doctor);
        $output->writeln('Created doctor user: doctor@example.com / doctor123');

        // Create Patient user
        $patient = new Patient();
        $patient->setEmail('patient@example.com');
        $patient->setFirstName('Jane');
        $patient->setLastName('Smith');
        $patient->setPhone('5551234567');
        $patient->setBirthDate(new \DateTime('1990-01-01'));
        $patient->setAddress('123 Main St, City');
        $patient->setGender('F');
        $patient->setBloodType('A+');
        $patient->setAllergies('Peanuts, Penicillin');
        
        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'patient123');
        $patient->setPassword($hashedPassword);
        
        $this->entityManager->persist($patient);
        $output->writeln('Created patient user: patient@example.com / patient123');

        $this->entityManager->flush();

        $output->writeln('All test users have been created successfully!');

        return Command::SUCCESS;
    }
}
