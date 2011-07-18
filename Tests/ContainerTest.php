<?php
/*
 * This file is part of ProFIT
 *
 * Copyright (c) 2011 Farheap Solutions (http://www.farheap.com)
 *
 * The unauthorized use of this code outside the boundaries of
 * Farheap Solutions Inc. is prohibited.
 */

namespace Opensoft\Bundle\DoctrineOXMBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Opensoft\Bundle\DoctrineOXMBundle\DependencyInjection\OpensoftDoctrineOXMExtension;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 *
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */ 
class ContainerTest extends TestCase
{
    public function getContainer()
    {
        require_once __DIR__.'/DependencyInjection/Fixtures/Bundles/AnnotationBundle/AnnotationBundle.php';

        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles'     => array('AnnotationBundle' => 'Opensoft\Bundle\DoctrineOXMBundle\Tests\DependencyInjection\Fixtures\Bundles\AnnotationBundle\AnnotationBundle'),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.debug'       => false,
        )));
        $loader = new OpensoftDoctrineOXMExtension();
        $container->registerExtension($loader);

        $configs = array();
        $configs[] = array(
            'xml_entity_managers' => array(
                'default' => array(
                    'mappings' => array(
                        'AnnotationBundle' => array()
                    ),
                ),
            )
        );
        $loader->load($configs, $container);

        $container->set('annotation_reader', new AnnotationReader());

        return $container;
    }

    public function testContainer()
    {
        $container = $this->getContainer();
//        print_r($container->getDefinitions());
        $this->assertInstanceOf('Doctrine\OXM\Mapping\Driver\DriverChain', $container->get('doctrine.oxm.metadata.chain'));
        $this->assertInstanceOf('Doctrine\OXM\Mapping\Driver\AnnotationDriver', $container->get('doctrine.oxm.metadata.annotation'));
        $this->assertInstanceOf('Doctrine\OXM\Mapping\Driver\XmlDriver', $container->get('doctrine.oxm.metadata.xml'));
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $container->get('doctrine.oxm.cache.array'));
        $this->assertInstanceOf('Doctrine\OXM\Marshaller\Marshaller', $container->get('doctrine.oxm.default_xml_marshaller'));
        $this->assertInstanceOf('Doctrine\OXM\Configuration', $container->get('doctrine.oxm.default_configuration'));
        $this->assertInstanceOf('Doctrine\OXM\Mapping\Driver\DriverChain', $container->get('doctrine.oxm.default_metadata_driver'));
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $container->get('doctrine.oxm.default_metadata_cache'));
        $this->assertInstanceOf('Doctrine\OXM\XmlEntityManager', $container->get('doctrine.oxm.default_xml_entity_manager'));
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $container->get('doctrine.oxm.cache'));
        $this->assertInstanceOf('Doctrine\OXM\XmlEntityManager', $container->get('doctrine.oxm.xml_entity_manager'));
        $this->assertInstanceOf('Doctrine\OXM\Marshaller\Marshaller', $container->get('doctrine.oxm.xml_marshaller'));
        $this->assertInstanceof('Doctrine\Common\EventManager', $container->get('doctrine.oxm.event_manager'));
    }
}
