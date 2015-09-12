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

    /**
     * @param $key string
     *
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

    public function getSite()
    {
        return $this->getHelperSet()->get('site');
    }

    protected function getNestedArrayHelper()
    {
        $nested_array = $this->getHelperSet()->get('nested-array');

        return $nested_array;
    }
}
