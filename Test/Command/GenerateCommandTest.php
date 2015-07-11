<?php

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Test\BaseTestCase;

abstract class GenerateCommandTest extends BaseTestCase
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface Drupal container
     */
    protected function getContainer()
    {
        $container = new Container();
        $container->set('twig', new \Twig_Environment());
        return $container;
    }

    protected function getHelperSet($input = null)
    {
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

        $translator = $this->getTranslationHelper();

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

        return new HelperSet([
          'formatter' => new FormatterHelper(),
          'drupal-autoload' => $autoload,
          'dialog' => $dialog,
          'stringUtils' => $stringUtils,
          'validators' => $validators,
          'translator' => $translator,
          'message' => $message,
          'chain' => $chain,
        ]);
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input . str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }


    protected function getTranslationHelper()
    {
        return $this->getTranslatorHelper();
    }

    public function getTranslatorHelper()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\TranslatorHelper')
          ->disableOriginalConstructor()
          ->setMethods(['loadResource', 'trans'])
          ->getMock();
    }
}
