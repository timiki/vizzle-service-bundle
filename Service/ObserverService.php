<?php

namespace Vizzle\ServiceBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Vizzle\ServiceBundle\Mapper\ServiceMapper;
use Vizzle\ServiceBundle\Manager\ServiceManager;
use Vizzle\ServiceBundle\Mapping;
use Vizzle\VizzleBundle\Process\ProcessUtils;
use Symfony\Bridge\Monolog\Logger;

/**
 * @Mapping\Process(
 *     name="service:observer",
 *     description="Services process observer.",
 *     mode="AUTO"
 * )
 */
class ObserverService implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    /**
     * @var ProcessUtils
     */
    protected $processUtils;

    /**
     * @var ServiceManager
     */
    protected $manager;

    /**
     * @var ServiceMapper
     */
    protected $mapper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @Mapping\OnStart()
     */
    public function onStart()
    {
        $this->processUtils = new ProcessUtils();
        $this->manager      = $this->container->get('vizzle.service.manager');
        $this->mapper       = $this->container->get('vizzle.service.mapper');
        $this->logger       = $this->container->get('logger');
    }

    /**
     * @Mapping\Execute()
     */
    public function execute()
    {
        $isRunCmd = [];

        $isRunCmd[] = count($this->processUtils->findRunCmd('vizzle:start')) > 2;
        $isRunCmd[] = count($this->processUtils->findRunCmd('vizzle:stop')) > 2;
        $isRunCmd[] = count($this->processUtils->findRunCmd('vizzle:restart')) > 2;
        $isRunCmd[] = count($this->processUtils->findRunCmd('service:start')) > 2;
        $isRunCmd[] = count($this->processUtils->findRunCmd('service:stop')) > 2;
        $isRunCmd[] = count($this->processUtils->findRunCmd('service:restart')) > 2;

        if (in_array(true, $isRunCmd)) {
            return;
        }

        // Find not started services.
        foreach ($this->mapper->getMetadata() as $meta) {

            if (
                $meta['mode'] == 'AUTO'
                && !$this->manager->isServiceRun($meta['name'])
                && $this->manager->isServiceEnabled($meta['name'])
                && $meta['name'] !== 'vizzle:observer'
            ) {

                // Service mark run as AUTO but not start, run it.

                $cmd = 'php ' . $this->container->get('kernel')->getConsoleCmd() . ' service:start ' . $meta['name'];

                // Is debug
                if ($this->container->get('kernel')->isDebug()) {
                    $cmd .= ' --debug';
                }

                $this->processUtils->runBackground($cmd);

                $this->logger->info(
                    sprintf(
                        'vizzle:observer: Start service %s.',
                        $meta['name']
                    )
                );

            }

        }

    }

    /**
     * @Mapping\OnStop()
     */
    public function onStop()
    {

        // On stop code...

    }

    /**
     * @Mapping\OnError()
     */
    public function onError()
    {

        // On error code...

    }
}
