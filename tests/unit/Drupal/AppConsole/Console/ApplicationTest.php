<?php
namespace Drupal\AppConsole\Console;

use \PHPUnit_Framework_TestCase as TestCase;
use \Symfony\Component\Console\Output\NullOutput;
use \Symfony\Component\Console\Input\ArrayInput;

class ApplicationTest extends TestCase {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  public function setUp() {
    $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                             ->getMock();
  }

  protected function getKernelMock() {
      $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
                        ->getMock();

      $container->expects($this->once())
                ->method('get')
                ->with('event_dispatcher')
                ->will($this->returnValue($this->dispatcher));

      $kernel = $this->getMockBuilder('Drupal\Core\DrupalKernel')
                     ->disableOriginalConstructor()
                     ->getMock();

      $kernel->expects($this->once())
             ->method('boot');

      $kernel->expects($this->once())
             ->method('getContainer')
             ->will($this->returnValue($container));

      return $kernel;
  }

  public function testCanRunApplication() {
    $kernel = $this->getKernelMock();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $this->assertEquals(0, $application->run(new ArrayInput(array()), new NullOutput()));
  }

  public function testShellOptionsUsesCustomShell() {

    $shell = $this->getMockBuilder('Drupal\AppConsole\Console\Shell')
                  ->disableOriginalConstructor()
                  ->getMock();

    $shell->expects($this->once())
          ->method('setProcessIsolation');

    $shell->expects($this->once())
          ->method('run');

    $shellHelper = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\ShellHelper')
                        ->disableOriginalConstructor()
                        ->getMock();

    $shellHelper->expects($this->once())
                ->method('getShell')
                ->will($this->returnValue($shell));

    $helperSet = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
                      ->getMock();

    $helperSet->expects($this->once())
              ->method('get')
              ->with('shell')
              ->will($this->returnValue($shellHelper));

    $kernel = $this->getKernelMock();

    $application = new Application($kernel);
    $application->setAutoExit(false);
    $application->setHelperSet($helperSet);

    $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArgvInput')
                  ->disableOriginalConstructor()
                  ->getMock();

    $input->expects($this->at(14))
          ->method('hasParameterOption')
          ->with(array('--shell', '-s'))
          ->will($this->returnValue(true));

    $input->expects($this->at(15))
          ->method('hasParameterOption')
          ->with(array('--process-isolation'));

    $this->assertEquals(0, $application->run($input, new NullOutput()));
  }

  public function testCanRunDrupalCommand() {

    $kernel = $this->getKernelMock();
    $application = new Application($kernel);
    $application->setAutoExit(false);

    $command = $this->getMockBuilder('\Drupal\AppConsole\Command\GeneratorModuleCommand')
                    ->disableOriginalConstructor()
                    ->getMock();

    $command->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

    $command->expects($this->any())
            ->method('getAliases')
            ->will($this->returnValue(array()));

    $command->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('generate:module'));

    $command->expects($this->once())
            ->method('run')
            ->will($this->returnValue(0));

    $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArgvInput')
                  ->disableOriginalConstructor()
                  ->getMock();

    $input->expects($this->once())
          ->method('getFirstArgument')
          ->will($this->returnValue('generate:module'));

    $this->dispatcher->expects($this->any())->method('dispatch');

    $application->add($command);

    $this->assertEquals(0, $application->run($input, new NullOutput()));
  }
}
