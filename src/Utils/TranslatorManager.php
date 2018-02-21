<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\TranslatorManager.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Core\Utils\TranslatorManager as TranslatorManagerBase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;

/**
 * Class TranslatorManager
 *
 * @package Drupal\Console\Utils
 */
class TranslatorManager extends TranslatorManagerBase
{
    protected $extensions = [];

    /**
     * @param $extensionPath
     */
    private function addResourceTranslationsByExtensionPath($extensionPath)
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
    private function addResourceTranslationsByModule($module)
    {
        if (!\Drupal::moduleHandler()->moduleExists($module)) {
            return;
        }
        $extensionPath = \Drupal::moduleHandler()->getModule($module)->getPath();
        $this->addResourceTranslationsByExtensionPath(
            $extensionPath
        );
    }

    /**
     * @param $theme
     */
    private function addResourceTranslationsByTheme($theme)
    {
        $extensionPath = \Drupal::service('theme_handler')->getTheme($theme)->getPath();
        $this->addResourceTranslationsByExtensionPath(
            $extensionPath
        );
    }

    /**
     * @param $library
     */
    private function addResourceTranslationsByLibrary($library)
    {
        /** @var \Drupal\Console\Core\Utils\DrupalFinder $drupalFinder */
        $drupalFinder = \Drupal::service('console.drupal_finder');
        $path =  $drupalFinder->getComposerRoot() . '/vendor/' . $library;
        $this->addResourceTranslationsByExtensionPath(
            $path
        );
    }

    /**
     * @param $extension
     * @param $type
     */
    public function addResourceTranslationsByExtension($extension, $type)
    {
        if (array_search($extension, $this->extensions) !== false) {
            return;
        }

        $this->extensions[] = $extension;
        if ($type == 'module') {
            $this->addResourceTranslationsByModule($extension);
            return;
        }
        if ($type == 'theme') {
            $this->addResourceTranslationsByTheme($extension);
            return;
        }
        if ($type == 'library') {
            $this->addResourceTranslationsByLibrary($extension);
            return;
        }
    }
}
