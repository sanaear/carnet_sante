<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-patient',
    description: 'Creates a new patient user',
)]
class CreatePatientCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'User password')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'User firstname')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'User lastname')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $firstname = $input->getOption('firstname');
        $lastname = $input->getOption('lastname');

        if (!$email || !$password || !$firstname || !$lastname) {
            $io->error('All options are required: email, password, firstname, lastname');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_PATIENT']);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Patient user created successfully: %s', $email));

        return Command::SUCCESS;
    }
} 