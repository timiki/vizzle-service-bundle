<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Vizzle\ServiceBundle\Manager\ServiceManager;
use Vizzle\VizzleBundle\Process\ProcessUtils;

class ServiceStartCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ProcessUtils
     */
    protected $utils;

    /**
     * @var ServiceManager
     */
    protected $manager;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('service:start')
            ->setDescription('Start service process in background')
            ->addArgument('service', InputArgument::REQUIRED, 'The service name')
            ->setHelp(<<<EOT
The <info>service:start</info> command run service process in backgrounds. Provide the service name as argument:

<info>php bin/console service:start</info>
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
        $this->utils   = new ProcessUtils();
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
            $this->io->writeln(
                sprintf(
                    'Service <info>%s</info> already start.',
                    $service
                )
            );

            return 1;
        }


        // Check pcntl_signal
        if (in_array('pcntl_signal', explode(',', ini_get('disable_functions')))) {
            $this->io->note('Attention pcntl_signal is disabled');
        }

        $rootDir = $this->getContainer()->get('kernel')->getRootDir();

        if (Kernel::VERSION_ID >= 30000) {
            $consolePath = realpath($rootDir . '/../bin/console');
        } else {
            $consolePath = realpath($rootDir . '/console');
        }

        $cmd = 'php ' . $consolePath . ' service:process ' . $service;

        // Is debug
        if ($this->getContainer()->get('kernel')->isDebug()) {
            $cmd .= ' --debug';
        }

        // Run in background
        $this->utils->runBackground($cmd);

        $message = ' * Start service <info>%s</info> ';

        // Wait for run
        $this->io->write(
            sprintf(
                $message,
                $service
            )
        );

        sleep(1);

        $wait      = 1;
        $maxWait   = 300;
        $result    = 0;
        $resultMsg = '<info>OK</info>' . PHP_EOL;

        while (!$this->manager->isServiceRun($service)) {
            $this->io->write('.');
            sleep(1);

            if ($wait === $maxWait) {
                $result    = 1;
                $resultMsg = '<error>FALSE</error>' . PHP_EOL;
                break;
            }

            $wait++;
        }

        $this->io->write($resultMsg);

        return $result;
    }
}

