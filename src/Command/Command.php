<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    /**
     * @var string
     */
    protected $module;
    protected $dependencies;
    /**
     * @var TranslatorHelper
     */
    protected $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
        parent::__construct();
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

    public function showMessages($output, $type = null)
    {
        if ($type) {
            $messages =  $this->messages[$type];
            return $this->getMessages($output, $messages, $type);
        }

        $messages = $this->messages[self::MESSAGE_ERROR];
        $this->getMessages($output, $messages, self::MESSAGE_ERROR);

        $messages = $this->messages[self::MESSAGE_WARNING];
        $this->getMessages($output, $messages, self::MESSAGE_WARNING);

        $messages = $this->messages[self::MESSAGE_INFO];
        $this->getMessages($output, $messages, self::MESSAGE_INFO);

        $messages = $this->messages[self::MESSAGE_SUCCESS];
        $this->getMessages($output, $messages, self::MESSAGE_SUCCESS);
    }

    private function getMessages($output, $messages, $type)
    {
        if ($messages) {
            foreach ($messages as $message) {
                $this->showMessage($output, $message, $type);
            }
        }
    }

    public function showMessage($output, $message, $type = self::MESSAGE_INFO)
    {
        if ($type == self::MESSAGE_ERROR) {
            $style = 'bg=red;fg=white';
        }
        if ($type == self::MESSAGE_WARNING) {
            $style = 'bg=magenta;fg=white';
        }
        if ($type == self::MESSAGE_INFO) {
            $style = 'bg=blue;fg=white';
        }
        if ($type == self::MESSAGE_SUCCESS) {
            $style = 'bg=green;fg=white';
        }
        $output->writeln([
          '',
          $this->getHelperSet()->get('formatter')->formatBlock(
            $message,
            $style,
            false
          ),
          '',
        ]);
    }

    public function showGeneratedFiles($output, $files)
    {
        if ($files) {
            $this->showMessage(
              $output,
              $this->trans('application.console.messages.generated.files')
            );
            $output->writeln(sprintf(
              '<info>%s:</info><comment>%s</comment>',
              $this->trans('application.site.messages.path'),
              DRUPAL_ROOT
            ));

            $index = 1;
            foreach ($files as $file) {
                $output->writeln(sprintf(
                  '<info>%s</info> - <comment>%s</comment>',
                  $index,
                  $file
                ));
                $index++;
            }
        }
    }

    /**
     * @param $key string
     * @return string
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
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

    public function addDependency($moduleName)
    {
        $this->dependencies[] = $moduleName;
    }

    public function getDependencies()
    {
        return $this->dependencies;
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');

        return $dialog;
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');

        return $question;
    }
}
