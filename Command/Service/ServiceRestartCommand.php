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
     * @var SymfonyStyle
     */
    protected $io;

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
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager   = $container->get('vizzle.service.manager');
        $service   = $input->getArgument('service');

        if (!$manager->isServiceExist($service)) {
            $this->io->error(
                sprintf(
                    'Service "%s" not exist',
                    $service
                )
            );

            return;
        }

        if (!$manager->isServiceRun($service)) {
            $this->io->writeln([
                    sprintf(
                        'Service "%s" not start.',
                        $service
                    ),
                    '',
                ]
            );
        } else {
            $command = $this->getApplication()->find('service:stop');
            $command->run(
                new ArrayInput(
                    [
                        'service' => $service,
                    ]),
                $output
            );

            sleep(5);

            $command = $this->getApplication()->find('service:start');
            $command->run(
                new ArrayInput(
                    [
                        'service' => $service,
                    ]),
                $output
            );
        }
    }
}

