<?php

namespace Wandi\EasyAdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Wandi\EasyAdminBundle\Entity\User;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class SetupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wandi:easy-admin:setup')
            ->setDescription('Download & install EasyAdminBundle, CKEditorBundle, CKFinderBundle assets');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('white', 'blue', array('bold'));
        $output->getFormatter()->setStyle('h1', $style);

        $commands = [
            [
                'name' => 'ckeditor:install',
            ],
            [
                'name' => 'ckfinder:download',
            ],
            [
                'name' => 'assets:install',
            ],
        ];

        foreach ($commands as $c){
            $commandName = $c['name'];
            $command = $this->getApplication()->find($commandName);

            try {

                $output
                    ->writeln(
                        [
                            '',
                            '<comment> -- </comment>',
                            '<comment> -- Wandi/EasyAdminBundle</comment>',
                            sprintf('<comment> -- %s </comment>', $commandName),
                            '<comment> -- </comment>',
                            '',
                        ]
                    );

                $returnCode = $command->run(
                    new ArrayInput(
                        [
                            'command' => $commandName,
                        ]
                    ),
                    $output
                );
            } catch (IOException $e) {
            } catch (\InvalidArgumentException $e){
            }
        }
    }
}