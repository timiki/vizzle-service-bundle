<?php

namespace Vizzle\ServiceBundle\Method\Services;

use Timiki\Bundle\RpcServerBundle\Mapping as RPC;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Vizzle\VizzleBundle\Method\AbstractMethod;

/**
 * @RPC\Method("services.get")
 */
class GetMethod extends AbstractMethod implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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

        return $this->serialize(
            $em
                ->getRepository('VizzleServiceBundle:Service')
                ->findAll()
        );
    }
}