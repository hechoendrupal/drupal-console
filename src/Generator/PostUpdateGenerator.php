<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PostUpdateGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PostUpdateGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PostUpdateGenerator constructor.
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
        $postUpdateFile =  $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.post_update.php';

        $parameters['file_exists'] = file_exists($postUpdateFile);

        $this->renderFile(
            'module/post-update.php.twig',
            $postUpdateFile,
            $parameters,
            FILE_APPEND
        );
    }
}
