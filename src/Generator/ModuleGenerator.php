<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleGenerator.
 */

namespace Drupal\Console\Generator;

/**
 * Class ModuleGenerator
 * @package Drupal\Console\Generator
 */
class ModuleGenerator extends Generator
{
    /**
     * @param $module
     * @param $machineName
     * @param $dir
     * @param $description
     * @param $core
     * @param $package
     * @param $feature
     * @param $composer
     * @param $dependencies
     */
    public function generate(
        $module,
        $machineName,
        $dir,
        $description,
        $core,
        $package,
        $feature,
        $composer,
        $dependencies
    ) {
        $dir .= '/'.$machineName;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" exists but is a file.',
                        realpath($dir)
                    )
                );
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not empty.',
                        realpath($dir)
                    )
                );
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $parameters = array(
          'module' => $module,
          'machine_name' => $machineName,
          'type' => 'module',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'feature' => $feature,
          'dependencies' => $dependencies,
        );

        $this->renderFile(
            'module/info.yml.twig',
            $dir.'/'.$machineName.'.info.yml',
            $parameters
        );

        $this->renderFile(
            'module/module.twig',
            $dir.'/'.$machineName.'.module',
            $parameters
        );

        if ($composer) {
            $this->renderFile(
                'module/composer.json.twig',
                $dir.'/'.'composer.json',
                $parameters
            );
        }
    }
}
