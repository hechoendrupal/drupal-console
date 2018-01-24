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
        $module = $parameters['machine_name'];
        $moduleFilePath =  $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.module';

        $parameters = array_merge($parameters, [
          'file_exists' => file_exists($moduleFilePath),
        ]);

        $this->renderFile(
            'module/help.php.twig',
            $moduleFilePath,
            $parameters,
            FILE_APPEND
        );
    }
}
