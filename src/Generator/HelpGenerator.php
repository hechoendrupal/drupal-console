<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\HelpGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Extension\Manager;

class HelpGenerator extends Generator implements GeneratorInterface
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * HelpGenerator constructor.
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
        $description = $parameters['description'];

        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $parameters = [
          'machine_name' => $module,
          'description' => $description,
          'file_exists' => file_exists($module_path . '/' . $module . '.module'),
        ];

        $this->renderFile(
            'module/help.php.twig',
            $module_path . '/' . $module . '.module',
            $parameters,
            FILE_APPEND
        );
    }
}
