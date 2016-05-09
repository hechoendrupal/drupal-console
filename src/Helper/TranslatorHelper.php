<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\TranslatorHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Drupal\Console\Helper\Helper;
use Drupal\Console\Utils\YamlFileDumper;

/**
 * Class TranslatorHelper
 * @package Drupal\Console\Helper
 */
class TranslatorHelper extends Helper
{
    /**
     * @var string
     */
    private $language;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param $resource
     * @param string   $name
     */
    private function addResource($resource, $name = 'yaml')
    {
        $this->translator->addResource(
            $name,
            $resource,
            $this->language
        );
    }

    /**
     * @param $loader
     * @param string $name
     */
    private function addLoader($loader, $name = 'yaml')
    {
        $this->translator->addLoader(
            $name,
            $loader
        );
    }

    /**
     * @param $language
     * @param $directoryRoot
     */
    public function loadResource($language, $directoryRoot)
    {
        $this->language = $language;
        $this->translator = new Translator($this->language);
        $this->addLoader(new ArrayLoader(), 'array');
        $this->addLoader(new YamlFileLoader(), 'yaml');

        $languageDirectory = $directoryRoot . 'config/translations/' . $language;
        if (!is_dir($languageDirectory)) {
            $languageDirectory = $directoryRoot . 'config/translations/en';
        }

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in($languageDirectory);

        foreach ($finder as $file) {
            $resource = $languageDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            // Handle application file different than commands
            if ($filename == 'application') {
                try {
                    $this->loadTranslationByFile($resource, 'application');
                } catch (ParseException $e) {
                    echo 'application.yml' . ' ' . $e->getMessage();
                }
            } else {
                $key = 'commands.' . $filename;
                try {
                    $this->loadTranslationByFile($resource, $key);
                } catch (ParseException $e) {
                    echo $key . '.yml ' . $e->getMessage();
                }
            }
        }
    }

    /**
     * Load yml translation where filename is part of translation key.
     *
     * @param $resource
     * @param $resourceKey
     */
    private function loadTranslationByFile($resource, $resourceKey= null)
    {
        $yaml = new Parser();
        $resourceParsed = $yaml->parse(file_get_contents($resource));

        if ($resourceKey) {
            $parents = explode(".", $resourceKey);
            $resourceArray = [];
            $this->setResourceArray($parents, $resourceArray, $resourceParsed);
            $resourceParsed = $resourceArray;
        }

        $this->addResource($resourceParsed, 'array');
    }

    /**
     * @param $parents
     * @param $parentsArray
     * @param $resource
     * @return mixed
     */
    private function setResourceArray($parents, &$parentsArray, $resource)
    {
        $ref = &$parentsArray;
        foreach ($parents as $parent) {
            $ref[$parent] = [];
            $previous = &$ref;
            $ref = &$ref[$parent];
        }

        $previous[$parent] = $resource;
        return $parentsArray;
    }

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
        $extensionPath = $this->getSite()->getModulePath($module);

        $this->addResourceTranslationsByExtension(
            $extensionPath
        );
    }

    /**
     * @param $theme
     */
    public function addResourceTranslationsByTheme($theme)
    {
        $extensionPath = $this->getSite()->getThemePath($theme);

        $this->addResourceTranslationsByExtension(
            $extensionPath
        );
    }

    /**
     * @param $extensionPath
     * @param $messages
     * @param $command_key
     */
    private function writeTranslationsByExtension($extensionPath, $messages, $command_key)
    {
        $translationFile = sprintf(
            '%s/console/translations/en/%s.yml',
            $extensionPath,
            $command_key
        );

        $yaml = $this->getContainerHelper()->get('yaml');
        $filesystem = $this->getContainerHelper()->get('filesystem');

        $filesystem->dumpFile($translationFile, $yaml::dump($messages));
    }

    /**
     * @param $module
     * @param $messages
     * @param $command_key
     */
    public function writeTranslationsByCommand($module, $messages, $command_key)
    {
        $extensionPath = $this->getSite()->getModulePath($module);

        $this->writeTranslationsByExtension(
            $extensionPath,
            $messages,
            $command_key
        );
    }

    /**
     * @param $theme
     * @param $messages
     * @param $command_key
     */
    public function writeTranslationsByTheme($theme, $messages, $command_key)
    {
        $extensionPath = $this->getSite()->getThemePath($theme);

        $this->writeTranslationsByExtension(
            $extensionPath,
            $messages,
            $command_key
        );
    }

    /**
     * @param $key
     * @return string
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
    }

    /**
     * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
     */
    public function getName()
    {
        return 'translator';
    }
}
