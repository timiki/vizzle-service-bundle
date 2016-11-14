<?php

namespace Vizzle\ServiceBundle\Command\Service;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vizzle\VizzleBundle\Process\ProcessUtils;

class ServiceStopCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('service:stop')
            ->setDescription('Stop service process in background')
            ->addArgument('service', InputArgument::REQUIRED, 'The service name')
            ->setHelp(<<<EOT
The <info>service:stop</info> command stop service process in backgrounds. Provide the service name as argument:

<info>php bin/console service:stop</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io    = new SymfonyStyle($input, $output);
        $utils = new ProcessUtils();

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
                    'Service <info>%s</info> already stop.',
                    $service
                )
            );

            return 1;
        }

        $utils->terminate($manager->getServicePid($service));

        $message = 'Stop service <info>%s</info> ';

        // Wait for stop
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

        while ($manager->isServiceRun($service)) {

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

