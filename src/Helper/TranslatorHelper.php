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

        $finder = new Finder();

        $languageDirectory = $directoryRoot . 'config/translations/' . $language;

        if (!is_dir($languageDirectory)) {
            $languageDirectory = $directoryRoot . 'config/translations/en';
        }

        $finder->files()
            ->name('*.yml')
            ->in($languageDirectory);

        foreach ($finder as $file) {
            $resource = $languageDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');
            // Handle application file different than commands

            if ($filename == 'application') {
                try {
                    $this->writeTranslationByFile($resource, 'application');
                } catch (ParseException $e) {
                    print 'application.yml' . ' ' . $e->getMessage();
                }
            } else {
                $key = 'commands.' . $filename;
                try {
                    $this->writeTranslationByFile($resource, $key);
                } catch (ParseException $e) {
                    print $key . '.yml ' . $e->getMessage();
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
    public function writeTranslationByFile($resource, $resourceKey= null)
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
    public function setResourceArray($parents, &$parentsArray, $resource)
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
     * @param $module
     */
    public function addResourceTranslationsByModule($module)
    {
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('module', $module).
          '/config/translations/console.'.$this->language.'.yml';

        if (file_exists($resource)) {
            $this->addResource($resource);
        } else {
            // Try to load the language fallback
            $resource_fallback = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('module', $module).
              '/config/translations/console.en.yml';
            if (file_exists($resource_fallback)) {
                $this->addResource($resource_fallback);
            }
        }
    }

    /**
     * @param $module
     * @param $messages
     */
    public function writeTranslationsByModule($module, $messages)
    {
        $currentMessages = $this->getMessagesByModule($module);

        $language = 'en';
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('module', $module).
          '/config/translations/';

        $messageCatalogue = new MessageCatalogue($language);
        if ($currentMessages && $currentMessages['messages']) {
            $messageCatalogue->add($currentMessages['messages'], 'console');
        }
        $messageCatalogue->add($messages, 'console');

        $translatorWriter = new TranslationWriter();
        $translatorWriter->addDumper('yaml', new YamlFileDumper());
        $translatorWriter->writeTranslations(
            $messageCatalogue,
            'yaml',
            ['path' => $resource, 'nest-level' => 10, 'indent' => 2]
        );
    }

    /**
     * @param $module
     * @return array
     */
    protected function getMessagesByModule($module)
    {
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('module', $module).
          '/config/translations/console.'.$this->language.'.yml';

        if (file_exists($resource)) {
            $moduleTranslator = new Translator($this->language);
            $moduleTranslator->addLoader('yaml', new YamlFileLoader());
            $moduleTranslator->addResource(
                'yaml',
                $resource,
                $this->language
            );

            return $moduleTranslator->getMessages($this->language);
        }

        return [];
    }

    /**
     * @param $theme
     */
    public function addResourceTranslationsByTheme($theme)
    {
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('theme', $theme).
        '/config/translations/console.'.$this->language.'.yml';

        if (file_exists($resource)) {
            $this->addResource($resource);
        } else {
            // Try to load the language fallback
            $resource_fallback = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('theme', $theme).
            '/config/translations/console.en.yml';
            if (file_exists($resource_fallback)) {
                $this->addResource($resource_fallback);
            }
        }
    }

    /**
     * @param $theme
     * @param $messages
     */
    public function writeTranslationsByTheme($theme, $messages)
    {
        $currentMessages = $this->getMessagesByModule($theme);

        $language = 'en';
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('theme', $theme).
        '/config/translations/';

        $messageCatalogue = new MessageCatalogue($language);
        if ($currentMessages && $currentMessages['messages']) {
            $messageCatalogue->add($currentMessages['messages'], 'console');
        }
        $messageCatalogue->add($messages, 'console');

        $translatorWriter = new TranslationWriter();
        $translatorWriter->addDumper('yaml', new YamlFileDumper());
        $translatorWriter->writeTranslations(
            $messageCatalogue,
            'yaml',
            ['path' => $resource, 'nest-level' => 10, 'indent' => 2]
        );
    }

    /**
     * @param $theme
     * @return array
     */
    protected function getMessagesByTheme($theme)
    {
        $resource = $this->getDrupalHelper()->getRoot().'/'.drupal_get_path('theme', $theme).
        '/config/translations/console.'.$this->language.'.yml';

        if (file_exists($resource)) {
            $themeTranslator = new Translator($this->language);
            $themeTranslator->addLoader('yaml', new YamlFileLoader());
            $themeTranslator->addResource(
                'yaml',
                $resource,
                $this->language
            );

            return $themeTranslator->getMessages($this->language);
        }

        return [];
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
