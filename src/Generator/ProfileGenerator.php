<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ProfileGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;

class ProfileGenerator extends Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $dir = $parameters['dir'];
        $machine_name = $parameters['machine_name'];

        $dir = ($dir == '/' ? '' : $dir) . '/' . $machine_name;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the profile as the target directory "%s" exists but is a file.',
                        realpath($dir)
                    )
                );
            }
            $files = scandir($dir);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the profile as the target directory "%s" is not empty.',
                        realpath($dir)
                    )
                );
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the profile as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $profilePath = $dir . '/' . $machine_name;


        $this->renderFile(
            'profile/info.yml.twig',
            $profilePath . '.info.yml',
            $parameters
        );

        $this->renderFile(
            'profile/profile.twig',
            $profilePath . '.profile',
            $parameters
        );

        $this->renderFile(
            'profile/install.twig',
            $profilePath . '.install',
            $parameters
        );
    }
}
