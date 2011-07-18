<?php

namespace Opensoft\Bundle\DoctrineOXMBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Bundle\DoctrineAbstractBundle\DependencyInjection\AbstractDoctrineExtension;

/**
 * Opensoft Doctrine OXM Extension
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class OpensoftDoctrineOXMExtension extends AbstractDoctrineExtension
{
    /**
     * Respond to the opensoft_doctrine_oxm configuration parameter
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load OpensoftDoctrineOXMBundle/Resources/config/oxm.xml
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('oxm.xml');

        $processor = new Processor();
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $processor->processConfiguration($configuration, $configs);

        // can't currently default this correctly in Configuration
        if (!isset($config['metadata_cache_driver'])) {
            $config['metadata_cache_driver'] = array('type' => 'array');
        }

        if (empty($config['default_xml_marshaller'])) {
            $keys = array_keys($config['xml_entity_managers']);
            $config['default_xml_marshaller'] = reset($keys);
        }

        if (empty($config['default_xml_entity_manager'])) {
            $keys = array_keys($config['xml_entity_managers']);
            $config['default_xml_entity_manager'] = reset($keys);
        }

        // load xml entity managers
        $this->loadXmlEntityManagers(
            $config['xml_entity_managers'],
            $config['default_xml_entity_manager'],
            $config['metadata_cache_driver'],
            $container
        );
    }

    /**
     * Loads the xml entity managers configuration
     *
     * @param array $emConfigs An array of xml entity manager configs
     * @param string $defaultXmlEm The default xml entity manager name
     * @param string $defaultMetadataCache The default metadata cache configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadXmlEntityManagers(array $emConfigs, $defaultXmlEm, $defaultMetadataCache, ContainerBuilder $container)
    {
        foreach ($emConfigs as $name => $xmlEntityManager) {
            $xmlEntityManager['name'] = $name;
            $this->loadXmlEntityManager(
                $xmlEntityManager,
                $defaultXmlEm,
                $defaultMetadataCache,
                $container
            );
        }
        $container->setParameter('doctrine.oxm.xml_entity_managers', array_keys($emConfigs));
        $container->setParameter('doctrine.oxm.xml_marshallers', array_keys($emConfigs));
    }

    /**
     * Loads an Xml Entity Manager
     *
     * @param array $xmlEntityManager An array of xml entity manager config data
     * @param string $defaultXmlEm The default xem name
     * @param string $defaultMetadataCache The default metadata cache configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadXmlEntityManager(array $xmlEntityManager, $defaultXmlEm, $defaultMetadataCache, ContainerBuilder $container)
    {
        $configServiceName = sprintf('doctrine.oxm.%s_configuration', $xmlEntityManager['name']);

        if ($container->hasDefinition($configServiceName)) {
            $xmlEntityConfigDef = $container->getDefinition($configServiceName);
        } else {
            $xmlEntityConfigDef = new Definition('%doctrine.oxm.configuration.class%');
            $container->setDefinition($configServiceName, $xmlEntityConfigDef);
        }

        $this->loadXmlEntityManagerBundlesMappingInformation($xmlEntityManager, $xmlEntityConfigDef, $container);
        $this->loadXmlEntityManagerMetadataCacheDriver($xmlEntityManager, $container, $defaultMetadataCache);
        $this->loadStorageDefinition($xmlEntityManager, $container);

        $methods = array(
            'setMetadataCacheImpl' => new Reference(sprintf('doctrine.oxm.%s_metadata_cache', $xmlEntityManager['name'])),
            'setMetadataDriverImpl' => new Reference(sprintf('doctrine.oxm.%s_metadata_driver', $xmlEntityManager['name'])),
        );

        foreach ($methods as $method => $arg) {
            if ($xmlEntityConfigDef->hasMethodCall($method)) {
                $xmlEntityConfigDef->removeMethodCall($method);
            }
            $xmlEntityConfigDef->addMethodCall($method, array($arg));
        }

        // event manager
        $eventManagerName = isset($xmlEntityManager['event_manager']) ? $xmlEntityManager['event_manager'] : $xmlEntityManager['name'];
        $eventManagerId = sprintf('doctrine.oxm.%s_event_manager', $eventManagerName);
        if (!$container->hasDefinition($eventManagerId)) {
            $eventManagerDef = new Definition('%doctrine.oxm.event_manager.class%');
            $eventManagerDef->addTag('doctrine.oxm.event_manager');
            $eventManagerDef->setPublic(false);
            $container->setDefinition($eventManagerId, $eventManagerDef);
        }

        $xmlEmArgs = array(
            new Reference(sprintf('doctrine.oxm.%s_storage', $xmlEntityManager['name'])),
            $xmlEntityConfigDef,
            new Reference($eventManagerId)
        );
        $xmlEmDef = new Definition('%doctrine.oxm.xml_entity_manager.class%', $xmlEmArgs);
        $xmlEmDef->addTag('doctrine.oxm.xml_entity_manager');

        $container->setDefinition(sprintf('doctrine.oxm.%s_xml_entity_manager', $xmlEntityManager['name']), $xmlEmDef);

        $xmlMarshallerDef = new Definition('%doctrine.oxm.xml_marshaller.class%');
        $xmlMarshallerDef->setFactoryMethod('getMarshaller');
        $xmlMarshallerDef->setFactoryService(new Reference(sprintf('doctrine.oxm.%s_xml_entity_manager', $xmlEntityManager['name'])));

        $container->setDefinition(sprintf('doctrine.oxm.%s_xml_marshaller', $xmlEntityManager['name']), $xmlMarshallerDef);

        if ($xmlEntityManager['name'] == $defaultXmlEm) {
            $container->setAlias(
                'doctrine.oxm.xml_entity_manager',
                new Alias(sprintf('doctrine.oxm.%s_xml_entity_manager', $xmlEntityManager['name']))
            );
            $container->setAlias(
                'doctrine.oxm.xml_marshaller',
                new Alias(sprintf('doctrine.oxm.%s_xml_marshaller', $xmlEntityManager['name']))
            );
            $container->setAlias(
                'doctrine.oxm.event_manager',
                new Alias(sprintf('doctrine.oxm.%s_event_manager', $xmlEntityManager['name']))
            );
        }
    }

    /**
     * Loads a storage definition
     *
     * @param array $storage A storage definition array
     * @param ContainerBuilder $container The ContainerBuilder instance
     */
    protected function loadStorageDefinition(array $xmlEntityManager, ContainerBuilder $container)
    {
        $storage = $xmlEntityManager['storage'];
        $storageDef = new Definition(sprintf('%%doctrine.oxm.storage.%s_storage.class%%', $storage['type']));
        $type = $storage['type'];

        if ('filesystem' == $type) {
            $storageDef->addArgument($storage['path']);
            $storageDef->addArgument($storage['extension']);
        }

        $container->setDefinition(sprintf('doctrine.oxm.%s_storage', $xmlEntityManager['name']), $storageDef);
    }

    /**
     * Loads the configured xml entity manager metadata cache driver.
     *
     * @param array $xmlEntityManager The configured xml entity manager array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param $defaultMetadataCache The default metadata cache configuration array
     */
    protected function loadXmlEntityManagerMetadataCacheDriver(array $xmlEntityManager, ContainerBuilder $container, $defaultMetadataCache)
    {
        $xemMetadataCacheDriver = isset($xmlEntityManager['metadata_cache_driver']) ? $xmlEntityManager['metadata_cache_driver'] : $defaultMetadataCache;
        $type = $xemMetadataCacheDriver['type'];

        if ('memcache' == $type) {
            $memcacheClass = isset($xemMetadataCacheDriver['class']) ? $xemMetadataCacheDriver['class'] : sprintf('%%doctrine.oxm.cache.%s.class%%', $type);
            $cacheDef = new Definition($memcacheClass);
            $memcacheHost = isset($xemMetadataCacheDriver['host']) ? $xemMetadataCacheDriver['host'] : '%doctrine.oxm.cache.memcache_host%';
            $memcachePort = isset($xemMetadataCacheDriver['port']) ? $xemMetadataCacheDriver['port'] : '%doctrine.oxm.cache.memcache_port%';
            $memcacheInstanceClass = isset($xemMetadataCacheDriver['instance-class']) ? $xemMetadataCacheDriver['instance-class'] : (isset($xemMetadataCacheDriver['instance_class']) ? $xemMetadataCacheDriver['instance_class'] : '%doctrine.oxm.cache.memcache_instance.class%');
            $memcacheInstance = new Definition($memcacheInstanceClass);
            $memcacheInstance->addMethodCall('connect', array($memcacheHost, $memcachePort));
            $container->setDefinition(sprintf('doctrine.oxm.%s_memcache_instance', $xmlEntityManager['name']), $memcacheInstance);
            $cacheDef->addMethodCall('setMemcache', array(new Reference(sprintf('doctrine.oxm.%s_memcache_instance', $xmlEntityManager['name']))));   
        } else {
            $cacheDef = new Definition(sprintf('%%doctrine.oxm.cache.%s.class%%', $type));
        }

        $container->setDefinition(sprintf('doctrine.oxm.%s_metadata_cache', $xmlEntityManager['name']), $cacheDef);
    }

    protected function loadXmlEntityManagerBundlesMappingInformation(array $xmlEntityManager, Definition $xemConfigDef, ContainerBuilder $container)
    {
        $this->drivers = array();
        $this->aliasMap = array();

        $this->loadMappingInformation($xmlEntityManager, $container);
        $this->registerMappingDrivers($xmlEntityManager, $container);

        if ($xemConfigDef->hasMethodCall('setXmlEntityNamespaces')) {
            $calls = $xemConfigDef->getMethodCalls();
            foreach ($calls as $call) {
                if ($call[0] == 'setXmlEntityNamespaces') {
                    $this->aliasMap = array_merge($call[1][0], $this->aliasMap);
                }
            }
            $method = $xemConfigDef->removeMethodCall('setXmlEntityNamespaces');
        }

        $xemConfigDef->addArgument('setXmlEntityNamespaces', array($this->aliasMap));
    }

    protected function getObjectManagerElementName($name)
    {
        return 'doctrine.oxm.' . $name;
    }

    protected function getMappingObjectDefaultName()
    {
        return 'XmlEntity';
    }

    protected function getMappingResourceExtension()
    {
        return 'oxm';
    }

    protected function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/doctrine';
    }

}
