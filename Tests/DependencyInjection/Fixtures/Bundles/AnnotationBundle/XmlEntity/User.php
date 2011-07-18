<?php
/*
 * This file is part of ProFIT
 *
 * Copyright (c) 2011 Farheap Solutions (http://www.farheap.com)
 *
 * The unauthorized use of this code outside the boundaries of
 * Farheap Solutions Inc. is prohibited.
 */

namespace Opensoft\Bundle\DoctrineOXMBundle\Tests\DependencyInjection\Fixtures\Bundles\AnnotationBundle\XmlEntity;

/**
 *
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 *
 * @XmlRootEntity
 */ 
class User 
{
    /**
     * @var integer
     *
     * @XmlAttribute(type="integer")
     */
    private $id;
}
