<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\BlockTypeGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class BlockTypeGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PermissionGenerator constructor.
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
        $class_name = $parameters['class_name'];
        $blockId = $parameters['block_id'];
        $description = $parameters['block_description'];
        $parameters['machine_name'] = $blockId;

        $this->renderFile(
            'module/src/Plugin/Block/blocktype.php.twig',
            $this->extensionManager->getPluginPath($module, 'Block') . '/' . $class_name . '.php',
            $parameters
        );

    }
}
