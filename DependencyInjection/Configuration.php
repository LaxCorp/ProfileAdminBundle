<?php

namespace LaxCorp\ProfileAdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @inheritdoc
 */
class Configuration implements ConfigurationInterface
{

    const ROOT = 'profile_admin';

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root($this::ROOT);

        $rootNode
            ->children()
            //->scalarNode('myparam')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}