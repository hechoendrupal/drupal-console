<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ThemeGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 *
 */
class ThemeGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
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
        $dir = $parameters['dir'];
        $breakpoints = $parameters['breakpoints'];
        $libraries = $parameters['libraries'];
        $machine_name = $parameters['machine_name'];
        $parameters['type'] = 'theme';

        $dir = ($dir == '/' ? '': $dir) . '/' . $machine_name;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the bundle as the target directory "%s" exists but is a file.',
                        realpath($dir)
                    )
                );
            }
            $files = scandir($dir);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the bundle as the target directory "%s" is not empty.',
                        realpath($dir)
                    )
                );
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the bundle as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $themePath = $dir . '/' . $machine_name;

        $this->renderFile(
            'theme/info.yml.twig',
            $themePath . '.info.yml',
            $parameters
        );

        $this->renderFile(
            'theme/theme.twig',
            $themePath . '.theme',
            $parameters
        );

        if ($libraries) {
            $this->renderFile(
                'theme/libraries.yml.twig',
                $themePath . '.libraries.yml',
                $parameters
            );
        }

        if ($breakpoints) {
            $this->renderFile(
                'theme/breakpoints.yml.twig',
                $themePath . '.breakpoints.yml',
                $parameters
            );
        }
    }
}
