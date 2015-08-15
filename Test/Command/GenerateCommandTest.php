<?php

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\DependencyInjection\Container;
use Drupal\AppConsole\Test\BaseTestCase;

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
