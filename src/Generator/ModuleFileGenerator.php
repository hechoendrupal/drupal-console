<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleFileGenerator.
 */

namespace Drupal\Console\Generator;

/**
 * Class ModuleFileGenerator
 * @package Drupal\Console\Generator
 */
class ModuleFileGenerator extends Generator
{
    /**
     * @param $machine_name
     * @param $file_path
     */
    public function generate(
        $machine_name,
        $file_path
    ) {
        $dir = $file_path .'/'. $machine_name. '.module';
      
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the .module file , it already exist at "%s"',
                        realpath($dir)
                    )
                );
            }
        }

        $parameters = array(
          'machine_name' => $machine_name,
          'file_path' => $file_path ,
        );

        if ($machine_name) {
            $this->renderFile(
                'module/module-file.twig',
                $file_path . '/' . $machine_name . '.module',
                $parameters
            );
        }
    }
}
