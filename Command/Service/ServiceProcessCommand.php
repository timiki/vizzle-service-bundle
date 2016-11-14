<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vizzle\ServiceBundle\Process\Process;

class ServiceProcessCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('service:process')
            ->setDescription('Service process')
            ->addArgument('service', InputArgument::REQUIRED, 'The service name')
            ->setHelp(<<<EOT
The <info>service:process</info> command run service process. Provide the service name as argument.:

<info>php bin/console service:process</info>
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

        if (!$manager->isServiceEnabled($service)) {

            $io->error(
                sprintf(
                    'Service "%s" is disabled for run.',
                    $service
                )
            );

            return 1;
        }

        if ($manager->isServiceRun($service)) {

            $io->warning(
                sprintf(
                    'Service "%s" already run.',
                    $service
                )
            );

        } else {

            $metadata = $manager->getServiceMetadata($service);

            // Create service object

            $class   = new $metadata['class'];
            $process = new Process(new $class, $manager, $container, $this->getApplication());

            // Run process loop
            $process->run();

        }

        return 0;
    }
}

