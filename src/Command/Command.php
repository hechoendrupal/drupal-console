<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class Command extends BaseCommand {

  /**
   * @var string
   */
  protected $module;

  protected $messages = [];

  /**
   * @var TranslatorHelper
   */
  protected $translator;

  function __construct($translator)
  {
    $this->translator = $translator;
    parent::__construct();
  }

  /**
   * @param $key string
   * @return string
   */
  public function trans($key){
    return $this->translator->trans($key);
  }

  protected function getDialogHelper()
  {
    $dialog = $this->getHelperSet()->get('dialog');

    return $dialog;
  }
  /**
   * @return TranslatorHelper
   */
  public function getTranslator()
  {
    return $this->translator;
  }

  /**
   * @param TranslatorHelper $translator
   */
  public function setTranslator($translator)
  {
    $this->translator = $translator;
  }

  /**
   * @return string
   */
  public function getModule()
  {
    return $this->module;
  }

  /**
   * @param string $module
   */
  public function setModule($module)
  {
    $this->module = $module;
  }

  public function showMessage($output, $welcomeMessage, $style = 'bg=blue;fg=white')
  {
    $output->writeln([
      '',
      $this->getHelperSet()->get('formatter')->formatBlock($welcomeMessage, $style, true),
      '',
    ]);
  }

  protected function getQuestionHelper()
  {
    $question = $this->getHelperSet()->get('question');

    return $question;
  }

  public function addMessage($message) {
    $this->messages[] = $message;
  }

  public function getMessages(){
    return $this->messages;
  }

  /**
   * @return \Drupal\AppConsole\Utils\StringUtils
   */
  public function getStringUtils()
  {
    $stringUtils = $this->getHelperSet()->get('stringUtils');

    return $stringUtils;
  }

  /**
   * @return \Drupal\AppConsole\Utils\Validators
   */
  public function getValidator()
  {
    $validators = $this->getHelperSet()->get('validators');

    return $validators;
  }

}