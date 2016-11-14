<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServiceStatusCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('service:status')
            ->setDescription('Show service status')
            ->addArgument('service', InputArgument::REQUIRED, 'The service name')
            ->setHelp(<<<EOT
The <info>service:stop</info> command show service status. Provide the service name as argument:

<info>php bin/console service:status</info>
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

            return 1;
        }

        if (!$manager->isServiceRun($service)) {

            $io->writeln(
                sprintf(
                    'Service <info>%s</info> stop.',
                    $service
                )
            );

        } else {

            $io->writeln(
                sprintf(
                    'Service <info>%s</info> start.',
                    $service
                )
            );

        }

        return 0;
    }
}

