<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vizzle\ServiceBundle\Manager\ServiceManager;
use Vizzle\ServiceBundle\Process\Process;

class ServiceProcessCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ServiceManager
     */
    protected $manager;

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

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io      = new SymfonyStyle($input, $output);
        $this->manager = $this->getContainer()->get('vizzle.service.manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument('service');

        if (!$this->manager->isServiceExist($service)) {
            $this->io->error(
                sprintf(
                    'Service "%s" not exist',
                    $service
                )
            );

            return 1;
        }

        if (!$this->manager->isServiceEnabled($service)) {
            $this->io->error(
                sprintf(
                    'Service "%s" is disabled for run.',
                    $service
                )
            );

            return 1;
        }

        if ($this->manager->isServiceRun($service)) {
            $this->io->warning(
                sprintf(
                    'Service "%s" already run.',
                    $service
                )
            );
        } else {
            $metadata = $this->manager->getServiceMetadata($service);

            // Create service object

            $class   = new $metadata['class'];
            $process = new Process(new $class, $this->manager, $this->getContainer(), $this->getApplication());

            // Run process loop
            $process->run();
        }

        return 0;
    }
}

