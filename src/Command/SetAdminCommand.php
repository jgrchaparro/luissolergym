<?php

namespace App\Command;

use App\Services\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:admin-create',
    description: 'Crear usuario admin',
)]
class SetAdminCommand extends Command
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Crear usuario admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Creando admin');

        $this->userService->setAdminUser();

        return Command::SUCCESS;
    }
}
