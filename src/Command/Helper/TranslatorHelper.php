<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Helper\TranslatorHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\MessageCatalogue;
use Drupal\AppConsole\YamlFileDumper;

class TranslatorHelper extends Helper
{
    private $language;

    /**
     * @var Translator
     */
    private $translator;

    private function addResource($resource)
    {
        $this->translator->addResource(
            'yaml',
            $resource,
            $this->language
        );
    }

    public function loadResource($language, $directoryRoot)
    {
        $resource_fallback = $directoryRoot.'config/translations/console.en.yml';
        $resource_language = $directoryRoot.'config/translations/console.'.$language.'.yml';

        if (!file_exists($resource_language)) {
            $language = 'en';
            $resource_language = $resource_fallback;
        }
        $this->language = $language;
        $this->translator = new Translator($this->language);
        $this->translator->addLoader('yaml', new YamlFileLoader());

        //Fallback to English (en)
        $this->addResource($resource_fallback);

        //Load user language
        if ($resource_language != $resource_fallback) {
            $this->addResource($resource_language);
        }
    }

    public function addResourceTranslationsByModule($module)
    {
        $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module).
          '/config/translations/console.'.$this->language.'.yml';

        if (file_exists($resource)) {
            $this->addResource($resource);
        }
    }

    public function writeTranslationsByModule($module, $messages)
    {
        $currentMessages = $this->getMessagesByModule($module);

        $language = 'en';
        $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module).
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

    protected function getMessagesByModule($module)
    {
        $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module).
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
