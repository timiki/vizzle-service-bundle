<?php

namespace Vizzle\ServiceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vizzle\ServiceBundle\Mapper\ServiceMapper;
use Vizzle\ServiceBundle\Manager\ServiceManager;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class VizzleServiceExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->setMapper($container);
        $this->setManager($container);
    }

    /**
     * Service mapping.
     *
     * @param ContainerBuilder $container
     */
    public function setMapper(ContainerBuilder $container)
    {
        $mapper = new Definition(ServiceMapper::class);
        $mapper->addMethodCall('setContainer', [new Reference('service_container')]);

        foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
            $mapper->addMethodCall('addPath', ['@' . $bundle . '/Service']);
        }

        $container->setDefinition('vizzle.service.mapper', $mapper);
    }

    /**
     * Service manager.
     *
     * @param ContainerBuilder $container
     */
    public function setManager(ContainerBuilder $container)
    {
        $manager = new Definition(ServiceManager::class);

        $manager->addMethodCall('setContainer', [new Reference('service_container')]);
        $manager->addMethodCall('setMapper', [new Reference('vizzle.service.mapper')]);

        $container->setDefinition('vizzle.service.manager', $manager);
    }
}
