<?php

/*
 * This file is part of the qandidate-labs/qandidate-toggle-bundle package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Qandidate\Bundle\ToggleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class QandidateToggleExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('services.xml');

        $processor = new Processor();
        $config    = $processor->processConfiguration(new Configuration(), $configs);

        $collection = 'in_memory';
        switch (true) {
            case 'redis' === $config['persistence']:
                $loader->load('redis.xml');

                $collection = 'predis';

                $container->setParameter('qandidate.toggle.redis.namespace', $config['redis_namespace']);
                $container->setAlias('qandidate.toggle.redis.client', $config['redis_client']);

                break;
            case 'factory' === $config['persistence']:
                $collection = 'factory';
                $definition = new DefinitionDecorator('qandidate.toggle.collection.in_memory');
                $definition->setFactory(array(
                    new Reference($config['collection_factory']['service_id']),
                    $config['collection_factory']['method']
                ));

                $container->setDefinition('qandidate.toggle.collection.factory', $definition);
                break;
        }

        $container->setAlias('qandidate.toggle.collection', 'qandidate.toggle.collection.' . $collection);

        $contextFactoryService = 'qandidate.toggle.user_context_factory';
        if (null !== $config['context_factory']) {
            $contextFactoryService = $config['context_factory'];
        }

        $container->setAlias('qandidate.toggle.context_factory', $contextFactoryService);
    }
}
