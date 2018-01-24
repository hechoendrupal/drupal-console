<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class FormAlterGenerator extends Generator
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
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

  /**
   * {@inheritdoc}
   */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $this->renderFile(
            'module/src/Form/form-alter.php.twig',
            $module_path . '/' . $module . '.module',
            $parameters,
            FILE_APPEND
        );
    }
}
