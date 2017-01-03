<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\TranslatorManager.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Core\Utils\TranslatorManager as TranslatorManagerBase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;

class TranslatorManager extends TranslatorManagerBase
{
    /**
     * @param $extensionPath
     */
    private function addResourceTranslationsByExtension($extensionPath)
    {
        $languageDirectory = sprintf(
            '%s/console/translations/%s',
            $extensionPath,
            $this->language
        );

        if (!is_dir($languageDirectory)) {
            return;
        }
        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in($languageDirectory);
        foreach ($finder as $file) {
            $resource = $languageDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');
            $key = 'commands.' . $filename;
            try {
                $this->loadTranslationByFile($resource, $key);
            } catch (ParseException $e) {
                echo $key . '.yml ' . $e->getMessage();
            }
        }
    }

    /**
     * @param $module
     */
    public function addResourceTranslationsByModule($module)
    {
        $extensionPath = \Drupal::service('module_handler')->getModule($module)->getPath();
        $this->addResourceTranslationsByExtension(
            $extensionPath
        );
    }

    /**
     * @param $theme
     */
    public function addResourceTranslationsByTheme($theme)
    {
        $extensionPath = \Drupal::service('theme_handler')->getTheme($theme)->getPath();
        $this->addResourceTranslationsByExtension(
            $extensionPath
        );
    }
}
