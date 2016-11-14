<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;

class ServiceRestartCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('service:restart')
            ->setDescription('Restart service process in background')
            ->addArgument('service', InputArgument::REQUIRED, 'The service name')
            ->setHelp(<<<EOT
The <info>service:restart</info> command restart service process in backgrounds. Provide the service name as argument:

<info>php bin/console service:restart</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io        = new SymfonyStyle($input, $output);
        $container = $this->getContainer();
        $manager   = $container->get('vizzle.service.manager');
        $service   = $input->getArgument('service');

        if (!$manager->isServiceExist($service)) {

            $io->error(
                sprintf(
                    'Service "%s" not exist',
                    $service
                )
            );

            return;
        }

        if (!$manager->isServiceRun($service)) {

            $io->writeln([
                    sprintf(
                        'Service "%s" not start.',
                        $service
                    ),
                    '',
                ]
            );

        } else {

            $this->getApplication()->doRun(
                new ArrayInput(
                    [
                        'command' => 'service:stop',
                        'service' => $service,
                    ]
                ),
                $output
            );

            sleep(5);

            $this->getApplication()->doRun(
                new ArrayInput(
                    [
                        'command' => 'service:start',
                        'service' => $service,
                    ]
                ),
                $output
            );

        }

    }
}

