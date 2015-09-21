<?php

namespace Drupal\Console\Test\Command;

use Symfony\Component\DependencyInjection\Container;
use Drupal\Console\Test\BaseTestCase;

abstract class GenerateCommandTest extends BaseTestCase
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface Drupal container
     */
    protected function getContainer()
    {
        $container = new Container();
        $container->set('twig', new \Twig_Environment());
        return $container;
    }
}
