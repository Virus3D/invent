<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Creates the first admin user')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }// end __construct()

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username for the admin account')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the admin account');
    }// end configure()

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        // Проверяем, есть ли уже пользователи.
        $count = $this->entityManager->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('user')
            ->executeQuery()
            ->fetchOne();

        if ((int) $count > 0) {
            $io->warning('Users already exist. Use "php bin/console app:create-user" for additional users.');

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('First admin user "%s" created successfully.', $username));

        return Command::SUCCESS;
    }// end execute()
}// end class
