<?php

namespace LaxCorp\ProfileAdminBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * {@inheritdoc}
 */
class ProfileAdminExtension extends ConfigurableExtension
{

    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {

        $config     = $mergedConfig;
        $confPrefix = Configuration::ROOT;

        foreach ($config as $key => $value) {
            $container->setParameter("{$confPrefix}.{$key}", $value);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

}