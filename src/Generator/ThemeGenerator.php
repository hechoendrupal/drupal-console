<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\ThemeGenerator.
 */
namespace Drupal\AppConsole\Generator;

class ThemeGenerator extends Generator
{
    public function generate(
      $theme,
      $machine_name,
      $dir,
      $description,
      $core,
      $package,
      $base_theme
    ) {
        $dir .= '/'.$machine_name;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.',
                  realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.',
                  realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.',
                  realpath($dir)));
            }
        }

        $parameters = array(
          'theme' => $theme,
          'machine_name' => $machine_name,
          'type' => 'theme',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'base_theme' => $base_theme,
        );

        $this->renderFile(
          'theme/info.yml.twig',
          $dir.'/'.$machine_name.'.info.yml',
          $parameters
        );

        $this->renderFile(
          'theme/theme.twig',
          $dir.'/'.$machine_name.'.theme',
          $parameters
        );
    }
}
