<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleFileGenerator.
 */

namespace Drupal\Console\Generator;

/**
 * Class ModuleGenerator
 * @package Drupal\Console\Generator
 */
class ModuleFileGenerator extends Generator
{
    /**
     * @param $module
     * @param $machineName
     */
    public function generate(
        $machine_name,
        $module_path
    ) { 
        $dir = $module_path .'/'. $machine_name. '.module';
        print_r($module_path);
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the .module file , it was already created at "%s"',
                        realpath($dir)
                    )
                );
            }
            
        }

        $parameters = array(
          'machine_name' => $machine_name,
          'module_path' => $module_path ,
        );

        if ($machine_name) {
            $this->renderFile(
                'module/module.twig',
                $module_path . '/' . $machine_name . '.module',
                $parameters
            );
        }
    }
}
