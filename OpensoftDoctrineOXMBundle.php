<?php

namespace Opensoft\Bundle\DoctrineOXMBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Opensoft\Bundle\DoctrineOXMBundle\DependencyInjection\OpensoftDoctrineOXMExtension;

/**
 * Opensoft Doctrine OXM Bundle
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class OpensoftDoctrineOXMBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OpensoftDoctrineOXMExtension();
    }
}
