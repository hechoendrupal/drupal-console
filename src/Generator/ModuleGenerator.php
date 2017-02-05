<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;

/**
 * Class ModuleGenerator
 *
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
     * @param $moduleFile
     * @param $featuresBundle
     * @param $composer
     * @param $dependencies
     * @param $test
     * @param $twigtemplate
     */
    public function generate(
        $module,
        $machineName,
        $dir,
        $description,
        $core,
        $package,
        $moduleFile,
        $featuresBundle,
        $composer,
        $dependencies,
        $test,
        $twigtemplate
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
            if ($files != ['.', '..']) {
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

        $parameters = [
          'module' => $module,
          'machine_name' => $machineName,
          'type' => 'module',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'dependencies' => $dependencies,
          'test' => $test,
          'twigtemplate' => $twigtemplate,
        ];

        $this->renderFile(
            'module/info.yml.twig',
            $dir.'/'.$machineName.'.info.yml',
            $parameters
        );

        if (!empty($featuresBundle)) {
            $this->renderFile(
                'module/features.yml.twig',
                $dir.'/'.$machineName.'.features.yml',
                [
                'bundle' => $featuresBundle,
                ]
            );
        }

        if ($moduleFile) {
            // Generate '.module' file.
            $this->createModuleFile($dir, $parameters);
        }

        if ($composer) {
            $this->renderFile(
                'module/composer.json.twig',
                $dir.'/'.'composer.json',
                $parameters
            );
        }

        if ($test) {
            $this->renderFile(
                'module/src/Tests/load-test.php.twig',
                $dir . '/src/Tests/' . 'LoadTest.php',
                $parameters
            );
        }
        if ($twigtemplate) {
            // If module file is not created earlier, create now.
            if (!$moduleFile) {
                // Generate '.module' file.
                $this->createModuleFile($dir, $parameters);
            }
            $this->renderFile(
                'module/module-twig-template-append.twig',
                $dir .'/' . $machineName . '.module',
                $parameters,
                FILE_APPEND
            );
            $dir .= '/templates/';
            if (file_exists($dir)) {
                if (!is_dir($dir)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" exists but is a file.',
                            realpath($dir)
                        )
                    );
                }
                $files = scandir($dir);
                if ($files != ['.', '..']) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" is not empty.',
                            realpath($dir)
                        )
                    );
                }
                if (!is_writable($dir)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" is not writable.',
                            realpath($dir)
                        )
                    );
                }
            }
            $this->renderFile(
                'module/twig-template-file.twig',
                $dir . $machineName . '.html.twig',
                $parameters
            );
        }
    }

    /**
     * Generate the '.module' file.
     *
     * @param string $dir
     *   The directory name.
     * @param array  $parameters
     *   The parameter array.
     */
    protected function createModuleFile($dir, $parameters)
    {
        $this->renderFile(
            'module/module.twig',
            $dir . '/' . $parameters['machine_name'] . '.module',
            $parameters
        );
    }
}
