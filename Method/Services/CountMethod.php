<?php

namespace Vizzle\ServiceBundle\Method\Services;

use Timiki\Bundle\RpcServerBundle\Mapping as RPC;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @RPC\Method("services.count")
 */
class CountMethod implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @Rpc\Param()
     */
    protected $paramName;

    /**
     * @Rpc\Execute()
     */
    public function execute()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        // Clear not active services
        $qb = $em->getRepository('VizzleServiceBundle:Service')->createQueryBuilder('service');
        $qb->delete();
        $qb->where('service.updatedAt < :date');
        $qb->setParameter('date', (new \DateTime())->sub(new \DateInterval('PT1M')));

        $qb->getQuery()->execute();

        $qb = $em->getRepository('VizzleServiceBundle:Service')->createQueryBuilder('service');
        $qb->select($qb->expr()->count('service.id'));

        return $qb->getQuery()->getSingleScalarResult();
    }
}