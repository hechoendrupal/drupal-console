<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Translator.
 */

namespace Drupal\Console\Utils;

use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem ;
use Symfony\Component\Yaml\Exception\ParseException;

class Translator
{
    /**
     * @var string
     */
    private $language;

    /**
     * @var BaseTranslator
     */
    private $translator;


    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Translator constructor.
     * @param Parser     $parser
     * @param Filesystem $filesystem
     */
    public function __construct(
        Parser $parser,
        Filesystem $filesystem
    ) {
        $this->parser = $parser;
        $this->filesystem = $filesystem;
    }

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
        $this->translator = new BaseTranslator($this->language);
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
        $resourceParsed = $this->parser->parse(file_get_contents($resource));

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

        $this->filesystem->dumpFile($translationFile, Yaml::dump($messages));
    }

    /**
     * @param $module
     * @param $messages
     * @param $command_key
     */
    public function writeTranslationsByCommand($module, $messages, $command_key)
    {
        $extensionPath = \Drupal::service('module_handler')->getModule($module)->getPath();

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
        $extensionPath = \Drupal::service('theme_handler')->getTheme($theme)->getPath();

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
}
