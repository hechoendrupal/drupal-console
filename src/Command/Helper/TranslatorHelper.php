<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\Helper\TranslatorHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\MessageCatalogue;
use Drupal\AppConsole\Utils\YamlFileDumper;

class TranslatorHelper extends Helper
{

  private $language;

  /**
   * @var $translator Translator
   */
  private $translator;

  public function loadResource($language, $directoryRoot){
    if (!file_exists($directoryRoot . 'config/translations/console.'.$language.'.yml')){
      $language = 'en';
    }
    $this->language = $language;

    $this->translator = new Translator($language);
    $this->translator->addLoader('yaml', new YamlFileLoader());
    $this->translator->addResource(
      'yaml',
      $directoryRoot . 'config/translations/console.'.$language.'.yml',
      $language
    );
  }

  public function addResourceTranslationsByModule($module){
    $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module) .
      '/config/translations/console.'.$this->language.'.yml';

    if (file_exists($resource)) {
      $this->translator->addResource(
        'yaml',
        $resource,
        $this->language
      );
    }
  }

  public function writeTranslationsByModule($module, $messages){
    $currentMessages = $this->getMessagesByModule($module);

    $language = 'en';
    $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module) .
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

  protected function getMessagesByModule($module){
    $resource = DRUPAL_ROOT.'/'.drupal_get_path('module', $module) .
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

  public function trans($key){
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
