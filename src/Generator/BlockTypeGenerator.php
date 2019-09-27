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

        $inputs = $parameters['inputs'];
        $module = $parameters['module'];
        $class_name = $parameters['class_name'];
        $twigTemplate = $parameters['twig_template'];
        $blockId = $parameters['block_id'];
        $description = $parameters['block_description'];
        $parameters['twig_template_name'] = str_replace('_', '-', $blockId);
        $parameters['machine_name'] = $blockId;

        $this->renderFile(
            'module/src/Plugin/Block/blocktype.php.twig',
            $this->extensionManager->getPluginPath($module, 'Block') . '/' . $class_name . '.php',
            $parameters
        );

        if ($twigTemplate) {
            $moduleDirectory = $this->extensionManager->getModule($module)->getPath();
            $moduleFilePath = $moduleDirectory . '/' . $module . '.module';

            $parameters['file_exist'] = file_exists($moduleFilePath);

            $this->renderFile(
                'module/module-block-twig-template-append.twig',
                $moduleFilePath,
                $parameters,
                FILE_APPEND
            );
            $moduleDirectory .= '/templates/';
            if (file_exists($moduleDirectory)) {
                if (!is_dir($moduleDirectory)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" exists but is a file.',
                            realpath($moduleDirectory)
                        )
                    );
                }
                if (!is_writable($moduleDirectory)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" is not writable.',
                            realpath($moduleDirectory)
                        )
                    );
                }
            }
            $this->renderFile(
                'module/templates/block-type-html.twig',
                $moduleDirectory . $parameters['twig_template_name'] . '.html.twig',
                $parameters
            );
        }
    }
}
