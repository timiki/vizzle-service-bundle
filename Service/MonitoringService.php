<?php

namespace Vizzle\ServiceBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Vizzle\ServiceBundle\Entity\Service;
use Vizzle\ServiceBundle\Manager\ServiceManager;
use Vizzle\ServiceBundle\Mapper\ServiceMapper;
use Vizzle\ServiceBundle\Mapping;

/**
 * @Mapping\Process(
 *     name="service:monitoring",
 *     description="Service monitoring service.",
 *     mode="AUTO"
 * )
 */
class MonitoringService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Service[]
     */
    protected $services;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var ServiceMapper
     */
    protected $serviceMapper;

    /**
     * @Mapping\OnStart()
     */
    public function onStart()
    {

        $this->serviceManager = $this->container->get('vizzle.service.manager');
        $this->serviceMapper  = $this->container->get('vizzle.service.mapper');
        $this->em             = $this->container->get('doctrine.orm.entity_manager');

        foreach ($this->serviceMapper->getMetadata() as $serviceMetadata) {

            $service = new Service();

            $service->setClass($serviceMetadata['class']);
            $service->setName($serviceMetadata['name']);
            $service->setDescription($serviceMetadata['description']);
            $service->setLifetime($serviceMetadata['lifetime']);

            if ($this->serviceManager->isServiceEnabled($serviceMetadata['name'])) {
                $service->setMode($serviceMetadata['mode']);
            } else {
                $service->setMode('DISABLE');
            }

            $service->setServer($this->container->getParameter('vizzle.server'));
            $service->setTimer($serviceMetadata['timer']);

            $this->em->persist($service);
            $this->services[] = $service;
        }

        $this->em->flush();
    }

    /**
     * @Mapping\OnStop()
     */
    public function onStop()
    {
        foreach ($this->services as $service) {
            $this->em->remove($service);
        }

        $this->em->flush();
    }

    /**
     * @Mapping\Execute()
     */
    public function execute()
    {
        foreach ($this->services as $service) {

            $service->setPid(
                $this->serviceManager->getServicePid($service->getName())
            );

            $service->setUpdatedAt(new \DateTime());
        }

        $this->em->flush();

        $this->clearOldRow();
    }

    /**
     * Clear old service row.
     */
    public function clearOldRow()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete();
        $qb->from('VizzleServiceBundle:Service', 'service');
        $qb->where('service.updatedAt < :date');
        $qb->setParameter('date', (new \DateTime())->sub(new \DateInterval('PT1M')));

        $qb->getQuery()->execute();
    }

    /**
     * Is server monitoring enabled
     */
    public function isEnabled()
    {
        if ($this->container->hasParameter('vizzle.service_monitoring.enabled')) {
            return (boolean)$this->container->getParameter('vizzle.service_monitoring.enabled');
        }

        return true;
    }
}
