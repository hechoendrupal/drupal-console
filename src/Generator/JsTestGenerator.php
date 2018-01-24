<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\JsTestGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class JsTestGenerator
 *
 * @package Drupal\Console\Generator
 */
class JsTestGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(Manager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $class = $parameters['class'];
        $module = $parameters['module'];

        $this->renderFile(
            'module/src/Tests/js-test.php.twig',
            $this->extensionManager->getModule($module)->getJsTestsPath() . "/$class.php",
            $parameters
        );
    }
}
