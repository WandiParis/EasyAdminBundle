<?php

namespace Wandi\EasyAdminBundle\Command;

use AppBundle\Exception\EAException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

class GeneratorEntityCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('wandi:easy-admin:generator:entity')
            ->setDescription('Create a specified entity file configuration for easy admin')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('force', 'f')
                ))
            )
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $container = $this->getContainer();
        $dirProject = $container->getParameter('kernel.project_dir');
        $eaToolParams = $container->getParameter('ea_tool');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('A easy admin config file for this entity, already exist, do you want to override it [<info>y</info>/n]?', true);
        $entity = $input->getArgument('entity');

        if (!file_exists($dirProject . '/app/config/easyadmin/' . $eaToolParams['pattern_file'] . '.yml'))
        {
            $output->writeln('You need to launch <info>ea:generate</info> command before launching this command.');
            return ;
        }

        if (!$input->getOption('force'))
        {
            if (file_exists($dirProject . '/app/config/easyadmin/config_easyadmin_' . $entity . '.yml'))
            {
                if (!$helper->ask($input, $output, $question))
                    return;
            }
        }

        try {
            $eaTool = $container->get('ea.entity');
            $eaTool->run($entity);
        } catch (EAException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

    }
}