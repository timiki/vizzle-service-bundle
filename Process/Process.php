<?php

namespace Vizzle\ServiceBundle\Process;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Vizzle\VizzleBundle\Process\ProcessUtils;
use Vizzle\ServiceBundle\Manager\ServiceManager;

/**
 * Service process container.
 */
class Process implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Is service process run.
     *
     * @var boolean
     */
    protected $isRun = false;

    /**
     * Is service restarted.
     *
     * @var boolean
     */
    protected $isRestart = false;

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $manager;

    /**
     * Service object.
     *
     * @var object|null
     */
    protected $service;

    /**
     * Filesystem.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Started at timestamp
     *
     * @var int
     */
    protected $startedAt;

    /**
     * Service metadata array.
     *
     * @var boolean
     */
    protected $metadata;

    /**
     * Loop.
     *
     * @var Loop
     */
    protected $loop;

    /**
     * Process utils.
     *
     * @var ProcessUtils
     */
    protected $processUtils;

    /**
     * Signals.
     *
     * @var array
     */
    private $signals = [
        SIGINT,
        SIGTERM,
    ];

    /**
     * Process constructor.
     *
     * @param object                                                         $service
     * @param ServiceManager|null                                            $manager
     * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
     *
     * @throws \Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException
     */
    final public function __construct($service, ServiceManager $manager = null, ContainerInterface $container = null)
    {
        $this->filesystem   = new Filesystem();
        $this->loop         = new Loop();
        $this->processUtils = new ProcessUtils();

        $this->setManager($manager);
        $this->setContainer($container);
        $this->setService($service);

        $self = $this;

        $this->loop->add(
            function () use (&$self) {
                $self->tick();
            }
        );

        $this->loop->onException(
            function ($e) use (&$self) {
                $self->onException($e);
            }
        );

        // When xdebug loaded processing will be abort after xdebug.max_nesting_level
        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        // Handler for signal
        if (!in_array('pcntl_signal', explode(',', ini_get('disable_functions')))) {
            foreach ($this->signals as $signal) {
                pcntl_signal($signal, [$this, 'signal']);
            }
        }

        // On shutdown terminate
        register_shutdown_function([$this, 'terminate']);
    }

    /**
     * Set service object.
     *
     * @param $service
     *
     * @return $this
     * @throws \Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException
     */
    public function setService($service)
    {
        if (!is_object($service)) {
            throw  new \RuntimeException(sprintf('$service must be object, %s given.', gettype($service)));
        }

        if (!$this->manager) {
            throw new \RuntimeException('Not set service manager.');
        }

        if (!$this->manager->getMapper()) {
            throw new \RuntimeException('Not set service mapper.');
        }

        if (!$metadata = $this->manager->getMapper()->getObjectMetadata($service)) {
            {
                throw new \RuntimeException('Not found metadata for service object. Service object must has @Process annotation.');
            }
        }

        // Interface access
        // TODO: to Service manager

        // Access to container
        if ($service instanceof ContainerAwareInterface) {
            $service->setContainer($this->container);
        }

        $this->metadata = $metadata;
        $this->service  = $service;

        $this->loop->setTimer($metadata['timer']);

        foreach ($this->metadata['execute'] as $method) {

            $this->loop->add(
                function () use ($service, $method) {
                    $service->{$method}();
                }
            );

        }

        return $this;
    }

    /**
     * Set service manager.
     *
     * @param ServiceManager $manager
     *
     * @return $this
     */
    public function setManager(ServiceManager $manager = null)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Run process.
     */
    public function run()
    {
        if (!$this->isRun) {
            $this->startedAt = time();
            $name            = $this->metadata['name'];

            if ($pid = $this->manager->getServicePid($name)) {
                throw new \RuntimeException(sprintf('Service %s already run with pid %s.', $name, $pid));
            }

            $this->isRun = true;
            $this->deleteVar();
            $this->createVar();
            $this->log(
                'NOTICE',
                sprintf(
                    'Service %s was started on %s.',
                    $name,
                    gethostname()
                )
            );

            // On start function.
            foreach ($this->metadata['onStart'] as $method) {

                try {

                    $this->service->{$method}();

                } catch (\Exception $e) {

                    $this->log(
                        'ERROR',
                        sprintf(
                            'Service %s on %s has error "%s" in onStart method, service stop.',
                            $this->metadata['name'],
                            gethostname(),
                            $e->getMessage()
                        ),
                        $e->getTrace()
                    );

                    $this->terminate();

                }

            }

            // Run loop
            $this->loop->run();

            // On stop
            if ($this->isRun) {
                $this->terminate();
            }
        }
    }

    /**
     * Logger.
     *
     * @param       $level
     * @param       $message
     * @param array $context
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->container) {
            $logger = $this->container->get('logger');
            $logger->log($level, $message, $context);

            unset($logger);
        }
    }

    /**
     * Delete service var.
     */
    protected function deleteVar()
    {
        $process = $this->manager->getVarDir() . '/' . strtolower($this->metadata['name']) . '.process';

        if (file_exists($process)) {
            $this->filesystem->remove($process);
        }
    }

    /**
     * Create service var.
     */
    protected function createVar()
    {
        $var = [
            'pid'         => getmypid(),
            'service'     => $this->metadata['name'],
            'mode'        => $this->metadata['mode'],
            'host'        => gethostname(),
            'startedAt'   => $this->startedAt,
            'description' => $this->metadata['description'],
            'class'       => $this->metadata['class'],
            'file'        => $this->metadata['file'],
            'lifetime'    => $this->metadata['lifetime'],
            'timer'       => $this->metadata['timer'],
        ];

        file_put_contents($this->getVarPath(), json_encode($var));
    }

    /**
     * Get service var values.
     *
     * @return array|null
     */
    protected function getVar()
    {
        $path = $this->getVarPath();

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return null;
    }

    /**
     * Get service var path.
     *
     * @return string
     */
    protected function getVarPath()
    {
        return $this->manager->getVarDir() . '/' . strtolower($this->metadata['name']) . '.process';
    }

    /**
     * Process terminate.
     */
    public function terminate()
    {
        if ($this->isRun) {

            $this->isRun = false;
            $this->loop->stop();

            $date = \DateTime::createFromFormat('U', $this->startedAt);
            $iv   = $date->diff(new \DateTime());

            foreach ($this->metadata['onStop'] as $method) {
                try {
                    $this->service->{$method}();
                } catch (\Exception $e) {
                    $this->log(
                        'ERROR',
                        sprintf(
                            'Service %s on %s has error "%s" in onStop method.',
                            $this->metadata['name'],
                            gethostname(),
                            $e->getMessage()
                        ),
                        $e->getTrace()
                    );
                }

            }

            $this->log(
                'NOTICE',
                sprintf(
                    'Service %s was terminated on %s after work time: %s.',
                    $this->metadata['name'],
                    gethostname(),
                    $iv->format('%yY %mM %dD %hH %iM %sS')
                )
            );

            $this->deleteVar();
        }
    }

    /**
     * On process signal.
     *
     * @param integer $signal
     */
    public function signal($signal)
    {
        switch ($signal) {
            case SIGINT:
            case SIGKILL:
            case SIGTERM:
                $this->terminate();
        }
    }

    /**
     * Restart process.
     */
    public function restart()
    {
        if (count($this->processUtils->findRunCmd('vizzle:stop')) === 2 && $this->isRun && !$this->isRestart) {
            $this->isRestart = true;
            $cmd             = 'php ' . $this->container->get('kernel')->getConsoleCmd() . ' service:restart ' . $this->metadata['name'];

            // Is debug
            if ($this->container->get('kernel')->isDebug()) {
                $cmd .= ' --debug';
            }

            $this->processUtils->runBackground($cmd);
        }
    }

    /**
     * On loop tick.
     */
    public function tick()
    {
        if ($this->isRun && $this->metadata['lifetime'] > 0) {

            // Check lifetime
            if ((time() - $this->startedAt) >= $this->metadata['lifetime'] && !$this->isRestart) {

                $this->log(
                    'NOTICE',
                    sprintf(
                        'Start restart service %s on %s by lifetime %s.',
                        $this->metadata['name'],
                        gethostname(),
                        $this->metadata['lifetime']
                    )
                );

                $this->restart();
            }

        }
    }

    /**
     * On loop exception.
     *
     * @param \Exception $e
     */
    protected function onException(\Exception $e)
    {
        $this->log(
            'ERROR',
            sprintf(
                'Service %s on %s has error "%s", service will be restart.',
                $this->metadata['name'],
                gethostname(),
                $e->getMessage()
            ),
            $e->getTrace()
        );

        // Call on error functions
        foreach ($this->metadata['onError'] as $method) {
            try {
                $this->service->{$method}();
            } catch (\Exception $e) {
                $this->log(
                    'ERROR',
                    sprintf(
                        'Service %s on %s has error "%s" in onError method.',
                        $this->metadata['name'],
                        gethostname(),
                        $e->getMessage()
                    ),
                    $e->getTrace()
                );
            }
        }

        // Restart
        $this->restart();
    }
}