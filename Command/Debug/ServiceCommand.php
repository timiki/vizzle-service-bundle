<?php

namespace Vizzle\ServiceBundle\Command\Debug;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServiceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('debug:service')
            ->setDescription('Displays current services list')
            ->setHelp(<<<EOT
The <info>debug:service</info> displays the services:

<info>php bin/console debug:service</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io     = new SymfonyStyle($input, $output);
        $mapper = $this->getContainer()->get('vizzle.service.mapper');

        $rows = [];

        foreach ($mapper->getMetadata() as $meta) {
            $rows[] = [
                $meta['name'],
                $meta['mode'],
                $meta['class'],
                $meta['description'],
            ];
        }

        $io->table([
            'Name',
            'Mode',
            'Class',
            'Description',
        ], $rows);
    }

}
