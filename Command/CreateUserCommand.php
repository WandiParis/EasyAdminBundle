<?php

namespace Wandi\EasyAdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wandi\EasyAdminBundle\Entity\User;

class CreateUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wandi:easy-admin:create-user')
            ->setDescription('Create an admin for Wandi/EasyAdminBundle')
            ->setDefinition(
                [
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setUsername($username)
            ->setPassword($container->get('security.password_encoder')->encodePassword($user, $password));

        $em->persist($user);
        $em->flush();

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));
    }
}