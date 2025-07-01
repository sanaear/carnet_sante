<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:setup-uploads',
    description: 'Create uploads directories with proper permissions'
)]
class SetupUploadsCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();
        $uploadsDir = $this->projectDir . '/public/uploads';
        $ordonnancesDir = $uploadsDir . '/ordonnances';

        try {
            // Create directories if they don't exist
            if (!$filesystem->exists($uploadsDir)) {
                $filesystem->mkdir($uploadsDir, 0775);
                $output->writeln('Created uploads directory');
            }

            if (!$filesystem->exists($ordonnancesDir)) {
                $filesystem->mkdir($ordonnancesDir, 0775);
                $output->writeln('Created ordonnances directory');
            }

            // Set proper permissions
            $filesystem->chmod($uploadsDir, 0775);
            $filesystem->chmod($ordonnancesDir, 0775);

            $output->writeln('Upload directories are ready');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error setting up upload directories: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
