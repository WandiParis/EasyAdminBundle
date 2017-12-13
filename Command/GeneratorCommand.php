<?php

namespace Wandi\EasyAdminBundle\Command;

use Wandi\EasyAdminBundle\Generator\Exception\EAException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('toto')
            ->setDescription('Create easy admin config files')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('force', 'f')
                ))
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $dirProject = $container->getParameter('kernel.project_dir');
        $eaToolParams = $container->getParameter('ea_tool');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('A easy admin config file, <info>already exist</info>, do you want to <info>override</info> it [<info>y</info>/n]?', true);

        $cleanCommand = $this->getApplication()->find('ea:clean');
        $greetInput = new ArrayInput([]);

        if (!$input->getOption('force')) {
            if (file_exists($dirProject . '/app/config/easyadmin/' . $eaToolParams['pattern_file'] . '.yml')) {
                if (!$helper->ask($input, $output, $question))
                    return;
            }
        }

        if (!is_dir($dirProject . '/app/config/easyadmin/')) {
            if (mkdir($dirProject . '/app/config/easyadmin/'))
                $output->writeln('<info>Easyadmin folder created successfully.</info>');
            else
                $output->writeln('<error>Unable to create easyadmin folder, the build process is stopped</error>');
        } else
            $cleanCommand->run($greetInput, $output);

        try {
            $eaTool = $container->get('ea.main');
            $eaTool->run();
        } catch (EAException $e) {
            $output->writeln('<error>(EAException catché)' . $e->getMessage() . '</error>');
        }
    }
}