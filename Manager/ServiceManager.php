<?php

namespace Vizzle\ServiceBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Vizzle\VizzleBundle\Mapper\MapperAwareInterface;
use Vizzle\VizzleBundle\Mapper\MapperAwareTrait;
use Vizzle\VizzleBundle\Process\ProcessUtils;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service manager.
 */
class ServiceManager implements ContainerAwareInterface, MapperAwareInterface
{
    use ContainerAwareTrait;
    use MapperAwareTrait;

    /**
     * Filesystem.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Process utils.
     *
     * @var ProcessUtils
     */
    protected $processUtils;

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->filesystem   = new Filesystem();
        $this->processUtils = new ProcessUtils();
    }

    /**
     * Get path to service var dir.
     *
     * @return string
     */
    public function getVarDir()
    {
        return $this->container->get('kernel')->getRootDir() . '/../var/service';
    }

    /**
     * Get service run return pid. If service not run return null.
     *
     * @param string $service
     *
     * @return null|integer
     * @throws \Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException
     */
    public function getServicePid($service)
    {
        if ($this->isServiceRun($service) && $var = $this->getProcessVar($service)) {
            return $var['pid'];
        }

        return null;
    }

    /**
     * Get service object instance.
     *
     * @param string $service
     *
     * @return null|mixed
     * @throws \Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException
     */
    public function getServiceObject($service)
    {
        if ($metadata = $this->getServiceMetadata($service)) {

            $service = new $metadata['class'];

            // Access to container
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($this->container);
            }

            return $service;
        }

        return null;
    }

    /**
     * Check is service enabled for run.
     *
     * @param string $service
     *
     * @return bool
     * @throws \Vizzle\Common\Mapper\Exceptions\InvalidMappingException
     */
    public function isServiceEnabled($service)
    {
        if ($service = $this->getServiceObject($service)) {

            $reflection = new \ReflectionObject($service);

            if ($reflection->hasMethod('isEnabled')) {
                return (boolean)$reflection->getMethod('isEnabled')->invoke($service);
            }

        }

        // By default service is enabled.
        return true;
    }

    /**
     * Check is service process run.
     *
     * @param string $service
     *
     * @return bool
     * @throws \Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException
     */
    public function isServiceRun($service)
    {
        if ($this->isServiceExist($service) && $var = $this->getProcessVar($service)) {
            return $this->processUtils->isExistPid($var['pid']);
        }

        return false;
    }

    /**
     * Get service process var.
     *
     * @param string $service
     *
     * @return array|null
     */
    public function getProcessVarPath($service)
    {
        return $this->getVarDir() . '/' . strtolower($service) . '.process';
    }

    /**
     * Get service process var.
     *
     * @param string $service
     *
     * @return array|null
     */
    public function getProcessVar($service)
    {
        if (file_exists(realpath($this->getProcessVarPath($service)))) {
            return json_decode(file_get_contents($this->getProcessVarPath($service)), true);
        }

        return null;
    }

    /**
     * Get all service process vars.
     *
     * @return array
     */
    public function getProcessVars()
    {
        $vars = [];

        if (file_exists($this->getVarDir())) {

            $varsDir = new \DirectoryIterator($this->getVarDir());

            foreach ($varsDir as $file) {

                if ($file->getExtension() === 'process') {

                    $vars[] = json_decode(file_get_contents($file->getPath()), true);

                }

            }

        }

        return $vars;
    }

    /**
     * Check is service exist.
     *
     * @param string $service Service name.
     *
     * @return bool
     * @throws \Vizzle\Common\Mapper\Exceptions\InvalidMappingException
     */
    public function isServiceExist($service)
    {
        foreach ($this->mapper->getMetadata() as $metadata) {
            if ($metadata['name'] === strtolower($service)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get service metadata array.
     *
     * @param string $service Service name.
     *
     * @return array|null
     * @throws Exceptions\ServiceNotFoundException
     */
    public function getServiceMetadata($service)
    {
        foreach ($this->mapper->getMetadata() as $metadata) {
            if ($metadata['name'] === strtolower($service)) {
                return $metadata;
            }
        }

        throw new Exceptions\ServiceNotFoundException('Service ' . $service . ' not found');
    }

    /**
     * Get list services
     *
     * @return array
     */
    public function getServices()
    {
        $service = [];

        foreach ($this->mapper->getMetadata() as $meta) {
            $service[] = $meta['name'];
        }

        return $service;
    }
}