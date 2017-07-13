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

    public function generate(
        $theme,
        $machine_name,
        $dir,
        $description,
        $core,
        $package,
        $base_theme,
        $global_library,
        $libraries,
        $regions,
        $breakpoints
    ) {
        $dir .= '/' . $machine_name;
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

        $parameters = [
        'theme' => $theme,
        'machine_name' => $machine_name,
        'type' => 'theme',
        'core' => $core,
        'description' => $description,
        'package' => $package,
        'base_theme' => $base_theme,
        'global_library' => $global_library,
        'libraries' => $libraries,
        'regions' => $regions,
        'breakpoints' => $breakpoints,
        ];

        $this->renderFile(
            'theme/info.yml.twig',
            $dir . '/' . $machine_name . '.info.yml',
            $parameters
        );

        $this->renderFile(
            'theme/theme.twig',
            $dir . '/' . $machine_name . '.theme',
            $parameters
        );

        if ($libraries) {
            $this->renderFile(
                'theme/libraries.yml.twig',
                $dir . '/' . $machine_name . '.libraries.yml',
                $parameters
            );
        }

        if ($breakpoints) {
            $this->renderFile(
                'theme/breakpoints.yml.twig',
                $dir . '/' . $machine_name . '.breakpoints.yml',
                $parameters
            );
        }
    }
}
