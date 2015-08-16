<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\ModuleGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ModuleGenerator extends Generator
{
    public function generate(
        $module,
        $machine_name,
        $dir,
        $description,
        $core,
        $package,
        $composer,
        $dependencies
    ) {
        $dir .= '/'.$machine_name;
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
          'machine_name' => $machine_name,
          'type' => 'module',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'dependencies' => $dependencies,
        );

        $this->renderFile(
            'module/info.yml.twig',
            $dir.'/'.$machine_name.'.info.yml',
            $parameters
        );

        $this->renderFile(
            'module/module.twig',
            $dir.'/'.$machine_name.'.module',
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
