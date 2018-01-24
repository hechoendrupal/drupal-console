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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $machineName = $parameters['machine_name'];
        $modulePath = $parameters['module_path'];
        $moduleFile = $parameters['module_file'];
        $featuresBundle = $parameters['features_bundle'];
        $composer = $parameters['composer'];
        $test = $parameters['test'];
        $twigTemplate = $parameters['twig_template'];

        $moduleDirectory = ($modulePath == '/' ? '': $modulePath) . '/' . $machineName;
        if (file_exists($moduleDirectory)) {
            if (!is_dir($moduleDirectory)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" exists but is a file.',
                        realpath($moduleDirectory)
                    )
                );
            }
            $files = scandir($moduleDirectory);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not empty.',
                        realpath($moduleDirectory)
                    )
                );
            }
            if (!is_writable($moduleDirectory)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not writable.',
                        realpath($moduleDirectory)
                    )
                );
            }
        }

        $parameters['type'] = 'module';

        $this->renderFile(
            'module/info.yml.twig',
            $moduleDirectory . '/' . $machineName . '.info.yml',
            $parameters
        );

        if (!empty($featuresBundle)) {
            $this->renderFile(
                'module/features.yml.twig',
                $moduleDirectory . '/' . $machineName . '.features.yml',
                [
                    'bundle' => $featuresBundle,
                ]
            );
        }

        if ($moduleFile) {
            $this->createModuleFile($moduleDirectory, $parameters);
        }

        if ($composer) {
            $this->renderFile(
                'module/composer.json.twig',
                $moduleDirectory . '/' . 'composer.json',
                $parameters
            );
        }

        if ($test) {
            $this->renderFile(
                'module/src/Tests/load-test.php.twig',
                $moduleDirectory . '/tests/src/Functional/' . 'LoadTest.php',
                $parameters
            );
        }
        if ($twigTemplate) {
            // If module file is not created earlier, create now.
            if (!$moduleFile) {
                // Generate '.module' file.
                $this->createModuleFile($moduleDirectory, $parameters);
            }
            $this->renderFile(
                'module/module-twig-template-append.twig',
                $moduleDirectory . '/' . $machineName . '.module',
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
                $files = scandir($moduleDirectory);
                if ($files != ['.', '..']) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to generate the templates directory as the target directory "%s" is not empty.',
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
                'module/twig-template-file.twig',
                $moduleDirectory . str_replace('_', '-', $machineName) . '.html.twig',
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
