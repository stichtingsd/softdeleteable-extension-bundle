<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class StichtingSDSoftDeleteableExtensionBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('metadata_cache')->defaultValue('cache.adapter.array')->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()->alias('stichtingsd.softdeleteable_extension.cache', $config['metadata_cache']);

        $container->import('../config/services.php');
    }
}
