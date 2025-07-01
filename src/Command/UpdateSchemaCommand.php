<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-schema',
    description: 'Update database schema',
)]
class UpdateSchemaCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Check database connection
            $this->entityManager->getConnection()->connect();
            $io->success('Successfully connected to the database');
            
            // Output the current database name
            $connection = $this->entityManager->getConnection();
            $databaseName = $connection->getDatabase();
            $io->writeln(sprintf('Database: <info>%s</info>', $databaseName));
            
            // Check if the ordonnance table exists
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            $io->writeln('\n<comment>Database tables:</comment>');
            foreach ($tables as $table) {
                $io->writeln(sprintf('  - %s', $table));
            }
            
            $io->success('Schema check completed');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error(sprintf('Database connection failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
