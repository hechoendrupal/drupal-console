<?php

namespace Drupal\AppConsole\Test;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\AppConsole\Command\Helper\DialogHelper;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    public $dir;

    /**
     * @var \Symfony\Component\Console\Helper\HelperSet
     */
    protected $helperSet;

    protected function setup()
    {
        $this->setUpTemporalDirectory();

        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', getcwd());
        }
    }

    public function setUpTemporalDirectory()
    {
        $this->dir = sys_get_temp_dir() . "/modules";
    }

    public function getHelperSet($input = null)
    {
        if (!$this->helperSet) {
            $dialog = new DialogHelper();
            $dialog->setInputStream($this->getInputStream($input));

            $autoload = $this
                ->getMockBuilder('Drupal\AppConsole\Command\Helper\DrupalAutoloadHelper')
                ->setMethods(['findAutoload', 'getDrupalRoot'])
                ->getMock();

            $stringUtils = $this->getMockBuilder('Drupal\AppConsole\Utils\StringUtils')
                ->disableOriginalConstructor()
                ->setMethods(['createMachineName'])
                ->getMock();

            $stringUtils->expects($this->any())
                ->method('createMachineName')
                ->will($this->returnArgument(0));

            $validators = $this->getMockBuilder('Drupal\AppConsole\Utils\Validators')
                ->disableOriginalConstructor()
                ->setMethods(['validateModuleName'])
                ->getMock();

            $validators->expects($this->any())
                ->method('validateModuleName')
                ->will($this->returnArgument(0));

            $translator = $this->getTranslatorHelper();

            $message = $this
                ->getMockBuilder('Drupal\AppConsole\Command\Helper\MessageHelper')
                ->disableOriginalConstructor()
                ->setMethods(['showMessages', 'showMessage'])
                ->getMock();

            $chain = $this
                ->getMockBuilder('Drupal\AppConsole\Command\Helper\ChainCommandHelper')
                ->disableOriginalConstructor()
                ->setMethods(['addCommand', 'getCommands'])
                ->getMock();

            $siteHelper = $this
                ->getMockBuilder('Drupal\AppConsole\Command\Helper\SiteHelper')
                ->disableOriginalConstructor()
                ->setMethods(['setModulePath', 'getModulePath'])
                ->getMock();

            $siteHelper->expects($this->any())
                ->method('getModulePath')
                ->will($this->returnValue($this->dir));

            $this->helperSet = new HelperSet(
                [
                'formatter' => new FormatterHelper(),
                'drupal-autoload' => $autoload,
                'dialog' => $dialog,
                'stringUtils' => $stringUtils,
                'validators' => $validators,
                'translator' => $translator,
                'site' => $siteHelper,
                'message' => $message,
                'chain' => $chain,
                ]
            );
        }

        return $this->helperSet;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input . str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    public function getTranslatorHelper()
    {
        $translatorHelper = $this
            ->getMockBuilder('Drupal\AppConsole\Command\Helper\TranslatorHelper')
            ->disableOriginalConstructor()
            ->setMethods(['loadResource', 'trans', 'getMessagesByModule', 'writeTranslationsByModule'])
            ->getMock();

        $translatorHelper->expects($this->any())
            ->method('getMessagesByModule')
            ->will($this->returnValue([]));

        return $translatorHelper;
    }
}
