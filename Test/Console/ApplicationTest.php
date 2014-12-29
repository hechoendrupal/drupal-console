<?php

namespace Drupal\AppConsole\Test\Console;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Drupal\AppConsole\Console\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @var \Symfony\Component\Console\Helper\HelperSet
   */
  protected $helperSet;

  /**
   * @var \Drupal\AppConsole\Command\Helper\BootstrapFinderHelper
   */
  protected $bootstrapFinder;

  /**
   * @var \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper
   */
  protected $register_commands;

  protected function setUp()
  {
    $this->helperSet = $this
        ->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
        ->getMock();
    
    $this->bootstrapFinder = $this
        ->getMockBuilder('Drupal\AppConsole\Command\Helper\BootstrapFinderHelper')
        ->disableOriginalConstructor()
        ->getMock();
    
    $this->register_commands = $this
        ->getMockBuilder('Drupal\AppConsole\Command\Helper\RegisterCommandsHelper')
        ->disableOriginalConstructor()
        ->getMock();

  }

  public function testCanRunApplication()
  {
    $this->expectsThatAutoloadFinderHelperIsRegistered();
    $this->expectsThatRegisterCommandsIsCalled();
    
    $application = new Application([]);
    $application->setAutoExit(false);
    $application->setHelperSet($this->helperSet);

    $this->assertEquals(0, $application->run(new ArrayInput([]), new NullOutput()));
  }

  protected function expectsThatAutoloadFinderHelperIsRegistered()
  {
    $this->bootstrapFinder->expects($this->any())
                    ->method('findBootstrapFile')
                    ->will($this->returnValue(false));
    
    $this->helperSet->expects($this->at(1))
                    ->method('get')
                    ->with('finder')
                    ->will($this->returnValue($this->bootstrapFinder));
  }

  protected function expectsThatRegisterCommandsIsCalled()
  {  
    $this->register_commands->expects($this->once())
      ->method('register')
      ->will($this->returnValue(true));
      
    $this->helperSet->expects($this->at(2))
      ->method('get')
      ->with('register_commands')
      ->will($this->returnValue($this->register_commands));
  }
}
