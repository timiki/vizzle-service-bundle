<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Vizzle\VizzleBundle\Process\ProcessUtils;

class ServiceStartCommand extends ContainerAwareCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io           = new SymfonyStyle($input, $output);
        $container    = $this->getContainer();
        $manager      = $container->get('vizzle.service.manager');
        $service      = $input->getArgument('service');
        $processUtils = new ProcessUtils();

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

            $io->writeln(
                sprintf(
                    'Service <info>%s</info> already start.',
                    $service
                )
            );

            return 1;
        }


        // Check pcntl_signal
        if (in_array('pcntl_signal', explode(',', ini_get('disable_functions')))) {
            $io->note('Attention pcntl_signal is disabled');
        }

        $rootDir = $container->get('kernel')->getRootDir();

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
        $processUtils->runBackground($cmd);

        $message = 'Start service <info>%s</info> ';

        // Wait for run
        $io->write(
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

        while (!$manager->isServiceRun($service)) {

            $io->write('.');
            sleep(1);

            if ($wait === $maxWait) {
                $result    = 1;
                $resultMsg = '<error>FALSE</error>' . PHP_EOL;
                break;
            }

            $wait++;
        }

        $io->write($resultMsg);

        return $result;
    }
}

