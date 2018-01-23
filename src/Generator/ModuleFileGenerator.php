<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleFileGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;

/**
 * Class ModuleFileGenerator
 *
 * @package Drupal\Console\Generator
 */
class ModuleFileGenerator extends Generator
{

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $machine_name = $parameters['machine_name'];
        $file_path = $parameters['file_path'];

        $moduleFilePath = $file_path . '/' . $machine_name . '.module';
      
        if (file_exists($moduleFilePath)) {
            if (!is_dir($moduleFilePath)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the .module file , it already exist at "%s"',
                        realpath($moduleFilePath)
                    )
                );
            }
        }

        if ($machine_name) {
            $this->renderFile(
                'module/module-file.twig',
              $moduleFilePath,
                $parameters
            );
        }
    }
}
