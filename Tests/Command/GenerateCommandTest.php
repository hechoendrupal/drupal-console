<?php

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper;

abstract class GenerateCommandTest extends \PHPUnit_Framework_TestCase {

  /**
   * @return Symfony\Component\DependencyInjection\Container Drupal container
   */
  protected function getContainer(){

    $container = new Container();
    return $container;
  }

  protected function getHelperSet($input){
    $dialog = new DialogHelper();
    $dialog->setInputStream($this->getInputStream($input));

    $bootstrap = $this
      ->getMockBuilder('Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper')
      ->setMethods(['getDrupalRoot'])
      ->getMock()
    ;

    return new HelperSet([
      'formatter' => new FormatterHelper(),
      'bootstrap' => $bootstrap,
      'dialog' => $dialog,
    ]);
  }

  protected function getInputStream($input) {
    $stream = fopen('php://memory', 'r+', false);
    fputs($stream, $input.str_repeat("\n", 10));
    rewind($stream);

    return $stream;
  }

}

