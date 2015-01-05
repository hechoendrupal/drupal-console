<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\Helper\TranslatorHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class TranslatorHelper extends Helper
{

  private $language;

  private $translator;

  public function loadResource($language, $directoryRoot){
    $this->language = $language;

    $this->translator = new Translator($language);
    $this->translator->addLoader('yaml', new YamlFileLoader());
    $this->translator->addResource(
      'yaml',
      $directoryRoot . 'config/translations/console.'.$this->language.'.yml',
      $language
    );
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
