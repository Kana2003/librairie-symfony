<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();
        $user->setEmail('admin@ecommerce.com');
        $user->setFirstName('Admin');
        $user->setLastName('E-commerce');
        $user->setPhone('514-555-0123');
        $user->setAddress('123 Rue Principale');
        $user->setCity('Montréal');
        $user->setPostalCode('H1A 1A1');
        $user->setProvince('QC');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(true);
        $user->setIsDeleted(false);

        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Administrateur créé avec succès !');
        $io->text('Email: admin@ecommerce.com');
        $io->text('Mot de passe: admin123');

        return Command::SUCCESS;
    }
}
