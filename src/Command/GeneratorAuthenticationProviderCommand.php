<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorAuthenticationProviderCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\FormTrait;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\AppConsole\Generator\AuthenticationProviderGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorAuthenticationProviderCommand extends GeneratorCommand
{
  use ServicesTrait;
  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  protected function configure()
  {
    $this
      ->setName('generate:authentication:provider')
      ->setDescription($this->trans('commands.generate.authentication.provider.description'))
      ->setHelp($this->trans('commands.generate.authentication.provider.help'))
      ->addOption('module','',InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption('class-name','',InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.authentication.provider.options.class-name'))
      ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $stringUtils = $this->getStringUtils();

    // @see use Drupal\AppConsole\Command\Helper\ConfirmationTrait::confirmationQuestion
    if ($this->confirmationQuestion($input, $output, $dialog)) {
      return;
    }

    $module = $input->getOption('module');
    $class_name = $input->getOption('class-name');

    $this->getGenerator()
         ->generate($module, $class_name)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $stringUtils = $this->getStringUtils();

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.authentication.provider.options.class-name'), 'DefaultAuthenticationProvider'),
        function ($value) use($stringUtils) {
            if (!strlen(trim($value))) {
              throw new \Exception('The Class name can not be empty');
            }
          return $stringUtils->humanToCamelCase($value);
        },
        false,
        'DefaultAuthenticationProvider'
      );
    }
    $input->setOption('class-name', $class_name);
  }

  protected function createGenerator()
  {
    return new AuthenticationProviderGenerator();
  }

}
