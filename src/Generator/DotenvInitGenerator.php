<?php

namespace Drupal\Console\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Core\Generator\Generator;

/**
 * Class InitGenerator
 *
 * @package Drupal\Console\Generator
 */
class DotenvInitGenerator extends Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $fs = new Filesystem();

        // Update settings.php File
        $settingsFile = $this->drupalFinder
                ->getDrupalRoot() . '/sites/default/settings.php';
        $settingsFileContent = file_get_contents($settingsFile);

        $settingsTwigContent = $this->renderer->render(
            'files/settings.php.twig',
            $parameters
        );

        file_put_contents(
            $settingsFile,
            $settingsFileContent .
            $settingsTwigContent
        );

        $fs->chmod($settingsFile, 0666);

        // Create .env File
        $envFile = $this->drupalFinder->getComposerRoot() . '/.env';
        $this->renderFile(
            'files/.env.dist.twig',
            $envFile,
            $parameters
        );
    }
}
