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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\OXM\Mapping\Driver\AnnotationDriver;
use Doctrine\OXM\XmlEntityManager;
use Doctrine\OXM\Storage\FileSystemStorage;
use Doctrine\OXM\Mapping\ClassMetadataFactory;
use Doctrine\OXM\Marshaller\XmlMarshaller;

/**
 *
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */ 
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\\OXM\\Version')) {
            $this->markTestSkipped('Doctrine OXM is not available');
        }
    }

    public static function createTestMarshaller($paths = array())
    {
        $config = new \Doctrine\OXM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), $paths));

        $metadataFactory = new ClassMetadataFactory($config);

        return new XmlMarshaller($metadataFactory);
    }

    public static function createTestEntityManager($paths = array())
    {
        $config = new \Doctrine\OXM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), $paths));

        return new XmlEntityManager(new FileSystemStorage(\sys_get_temp_dir()), $config);
    }
}
