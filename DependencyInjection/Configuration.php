<?php

namespace Opensoft\Bundle\DoctrineOXMBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * OpensoftDoctrineOXMExtension configuration structure
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * Constructor.
     *
     * @param Boolean $debug The kernel.debug value
     */
    public function __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('opensoft_doctrine_oxm');

        $this->addXmlEntityManagersSection($rootNode);

        $rootNode
            ->children()
                ->scalarNode('default_xml_entity_manager')->end()
                ->scalarNode('default_xml_marshaller')->end()
            ->end()
        ;

        return $treeBuilder;
    }


    /**
     * Builds OXM specific configuration options
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addXmlEntityManagersSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('xml_entity_manager')
            ->children()
                ->arrayNode('xml_entity_managers')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->treatNullLike(array())
                        ->children()
                            ->scalarNode('xml_marshaller')->end()
                            ->scalarNode('auto_mapping')->defaultFalse()->end()
                            ->arrayNode('metadata_cache_driver')
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return !is_array($v); })
                                    ->then(function($v) { return array('type' => $v); })
                                ->end()
                                ->children()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('port')->end()
                                    ->scalarNode('instance_class')->end()
                                ->end()
                            ->end()
                            ->arrayNode('storage')
                                ->addDefaultsIfNotSet()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return !is_array($v); })
                                    ->then(function($v) { return array('type' => $v); })
                                ->end()
                                ->children()
                                    ->scalarNode('type')->defaultValue('filesystem')->end()
                                    ->scalarNode('path')->defaultValue('%kernel.cache_dir%/doctrine/oxm/xml')->end()
                                    ->scalarNode('extension')->defaultValue('xml')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('mapping')
                        ->children()
                            ->arrayNode('mappings')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function($v) { return array ('type' => $v); })
                                    ->end()
                                    ->treatNullLike(array())
                                    ->treatFalseLike(array('mapping' => false))
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('mapping')->defaultValue(true)->end()
                                        ->scalarNode('type')->end()
                                        ->scalarNode('dir')->end()
                                        ->scalarNode('prefix')->end()
                                        ->scalarNode('alias')->end()
                                        ->booleanNode('is_bundle')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
