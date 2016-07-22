<?php

/**
 * @file
 * Contains Drupal\Console\Helper\ContainerHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ContainerHelper
 * @package Drupal\Console\Helper
 */
class ContainerHelper extends Helper
{
    /**
     * @var $container ContainerBuilder
     */
    private $container;

    /**
     * ContainerHelper constructor.
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }

        return null;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'container';
    }
}
