Overview
========

Provides simple bundle for loading Doctrine's OXM library.

Installation
============

Vendors style.  Add the following to your deps.

    [OpensoftDoctrineOXMBundle]
        git=http://github.com/opensoft/OpensoftDoctrineOXMBundle.git
        target=/bundles/Opensoft/Bundle/DoctrineOXMBundle

Add to AppKernel.php

    new Opensoft\Bundle\DoctrineOXMBundle\OpensoftDoctrineOXMBundle()


Configuration
=============

Settings below

    opensoft_doctrine_oxm:
        xml_entity_managers:
            xem1:
                mappings:
                    MyBundle1: ~
                    MyBundle3: { type: annotation, dir: Documents/ }
                storage:
                    type: filesystem
                    path: %kernel.cache_dir%/doctrine/oxm/xml
                    extension: xml
