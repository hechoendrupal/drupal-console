<?php

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\AppConsole\Command\Helper\DialogHelper;

abstract class GenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Symfony\Component\DependencyInjection\Container Drupal container
     */
    protected function getContainer()
    {
        $container = new Container();
        $container->set('twig', new \Twig_Environment());
        return $container;
    }

    protected function getHelperSet($input)
    {
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream($input));

        $bootstrap = $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper')
          ->setMethods(['getDrupalRoot'])
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
          'bootstrap' => $bootstrap,
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
        return $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\TranslatorHelper')
          ->disableOriginalConstructor()
          ->setMethods(['loadResource', 'trans'])
          ->getMock();
    }
}
