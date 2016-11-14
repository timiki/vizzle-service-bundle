<?php

namespace Vizzle\ServiceBundle\Command\Services;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServicesStatusCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('services:status')
            ->setDescription('Show services status info')
            ->setHelp(<<<EOT
The <info>services:status</info> command show services status info:

<info>php bin/console services:status</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io        = new SymfonyStyle($input, $output);
        $container = $this->getContainer();
        $manager   = $container->get('vizzle.service.manager');
        $mapper    = $this->getContainer()->get('vizzle.service.mapper');

        $rows = [];

        foreach ($mapper->getMetadata() as $meta) {

            $var     = $manager->getProcessVar($meta['name']);
            $run     = $manager->isServiceRun($meta['name']);
            $enabled = $manager->isServiceEnabled($meta['name']);
            $status  = $enabled ? ($run ? 'RUN' : 'STOP') : 'DISABLE';

            $service             = [];
            $service['name']     = $meta['name'];
            $service['status']   = $status;
            $service['mode']     = $meta['mode'];
            $service['lifetime'] = $meta['lifetime'];

            if ($var && $run) {

                $service['pid']  = $var['pid'];
                $date            = \DateTime::createFromFormat('U', $var['startedAt']);
                $iv              = $date->diff(new \DateTime());
                $service['time'] = $iv->format('%yY %mM %dD %hH %iM %sS');

            }

            foreach ($service as $key => $value) {

                switch ($status) {
                    case 'RUN':
                        $value = '<fg=green>' . $value . '</>';
                        break;
                    case 'STOP':
                        $value = '<fg=yellow>' . $value . '</>';
                        break;
                    case 'DISABLE':
                        $value = '<fg=white>' . $value . '</>';
                        break;
                }

                $service[$key] = $value;

            }

            $rows[] = $service;
        }

        $io->table([
            'Name',
            'Status',
            'Mode',
            'Lifetime',
            'Pid',
            'Run',
        ], $rows);

        return 0;
    }
}

