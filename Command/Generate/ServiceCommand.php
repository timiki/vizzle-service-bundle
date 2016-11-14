<?php

namespace Vizzle\ServiceBundle\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vizzle\VizzleBundle\Command\Generate\AbstractCommand;

class ServiceCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:service')
            ->setDescription('Generates a new service')
            ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'The bundle where the service is generated')
            ->addOption('name', 't', InputOption::VALUE_OPTIONAL, 'The service name')
            ->setHelp(<<<EOT
The <info>generate:service</info> command helps you generate new service
inside bundles:

<info>php bin/console generate:service</info>
EOT
            );
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generate new service');

        // Bundle name
        if (empty($input->getOption('bundle'))) {

            $input->setOption(
                'bundle',
                $io->ask('Bundle name', null, [$this, 'isValidBundle'])
            );

        }

        // Service name
        if (empty($input->getOption('name'))) {

            $input->setOption(
                'name',
                $io->ask('Service name', null, [$this, 'isValidName'])
            );

        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $bundle = $this
            ->getContainer()
            ->get('kernel')
            ->getBundle(
                $this->isValidBundle($input->getOption('bundle'))
            );
        $name   = $this->isValidName($input->getOption('name'));

        $filesystem = new Filesystem();
        $bundleDir  = $bundle->getPath();
        $serviceDir = $bundleDir . '/Service';

        $filesystem->mkdir($serviceDir);

        $serviceClassName = $this->classify($name) . 'Service';
        $serviceFile      = $serviceDir . '/' . $serviceClassName . '.php';

        if ($filesystem->exists($serviceFile)) {
            throw new \RuntimeException(sprintf(
                'Service "%s" already exists',
                $name
            ));
        }

        $parameters = [
            'namespace' => $bundle->getNamespace(),
            'class'     => $serviceClassName,
            'name'      => $name,
        ];

        $this->renderFile('Service.php.twig', $serviceFile, $parameters);

        $io->success(sprintf(
            'Service "%s" was generate in file "%s".',
            $name,
            $serviceFile
        ));
    }

    /**
     * Get the twig environment path to skeletons.
     *
     * @return string
     */
    public function getTwigPath()
    {
        return dirname(__DIR__) . '/../Resources/skeleton';
    }

    public function isValidName($name)
    {
        $name = ltrim($name, '=:');

        if (empty($name)) {
            throw new \RuntimeException('Service name can`t be empty.');
        }

        $name = str_replace(' ', ':', $name);

        if ($this->getContainer()->get('vizzle.service.manager')->isServiceExist($name)) {
            throw new \RuntimeException(sprintf(
                'Service "%s" already exist.',
                $name
            ));
        }

        return $name;
    }

    public function isValidBundle($bundle)
    {
        $bundle = ltrim($bundle, '=:');

        try {
            $this->getContainer()->get('kernel')->getBundle($bundle);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Bundle "%s" does not exist.',
                $bundle
            ));
        }

        return $bundle;
    }
}
