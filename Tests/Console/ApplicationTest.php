<?php

namespace Drupal\AppConsole\Test\Console;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Drupal\AppConsole\Console\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var \Symfony\Component\Console\Helper\HelperSet
   */
  protected $helperSet;

  /**
   * @var \Drupal\AppConsole\Command\Helper\BootstrapFinderHelper
   */
  protected $bootstrapFinder;

  /**
   * @var \Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper
   */
  protected $drupalBootstrap;

  /**
   * @var \Drupal\AppConsole\Command\Helper\KernelHelper
   */
  protected $kernel;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\AppConsole\Command\Helper\ShellHelper
   */
  protected $shellHelper;

  /**
   * @var \Drupal\AppConsole\Console\Shell
   */
  protected $shell;

  /**
   * @var \Drupal\AppConsole\Command\GeneratorModuleCommand
   */
  protected $command;

  /**
   * @var \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper
   */
  protected $register_commands;

  /**
   * @var \Symfony\Component\Console\Input\ArgvInput
   */
  protected $input;

  protected function setUp() {
    $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                             ->getMock();
    $this->helperSet = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
                            ->getMock();
    $this->bootstrapFinder = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\BootstrapFinderHelper')
                                  ->disableOriginalConstructor()
                                  ->getMock();
    $this->drupalBootstrap = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper')
                                  ->getMock();
    $this->kernel = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\KernelHelper')
                         ->getMock();
    $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
                                  ->getMock();
    $this->shell = $this->getMockBuilder('Drupal\AppConsole\Console\Shell')
                        ->disableOriginalConstructor()
                        ->getMock();
    $this->shellHelper = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\ShellHelper')
                              ->disableOriginalConstructor()
                              ->getMock();
    $this->input = $this->getMockBuilder('Symfony\Component\Console\Input\ArgvInput')
                        ->disableOriginalConstructor()
                        ->getMock();
    $this->command = $this->getMockBuilder('Drupal\AppConsole\Command\GeneratorModuleCommand')
                          ->disableOriginalConstructor()
                          ->getMock();
    $this->register_commands = $this->getMockBuilder('Drupal\AppConsole\Command\Helper\RegisterCommandsHelper')
                                    ->disableOriginalConstructor()
                                    ->getMock();
  }

  public function testCanRunApplication() {
    $this->expectsThatDrupalBootstrapHelperIsRegistered();
    $this->expectsThatBootstrapFinderHelperIsRegistered();
    $this->expectsThatKernelHelperIsRegistered();
    $this->expectsThatKernelHelperIsRetrievedToGetDrupalKernelConfigured();
    $this->expectsThatKernelHelperIsCalledToConfigureDrupalKernel();
    $this->expectsThatRegisterCommandsIsCalled();
    $this->expectsThatDrupalConsoleRegisterCommands();

    $application = new Application();
    $application->setAutoExit(false);
    $application->setHelperSet($this->helperSet);

    $this->assertEquals(0, $application->run(new ArrayInput(array()), new NullOutput()));
  }

  public function testApplicationUsesDrupalShell() {
    $this->expectsThatDrupalBootstrapHelperIsRegistered();
    $this->expectsThatBootstrapFinderHelperIsRegistered();
    $this->expectsThatKernelHelperIsRegistered();
    $this->expectsThatKernelHelperIsRetrievedToGetDrupalKernelConfigured();
    $this->expectsThatKernelHelperIsCalledToConfigureDrupalKernel();
    $this->expectsThatRegisterCommandsIsCalled();
    $this->expectsThatDrupalConsoleRegisterCommands();

    $this->expectsThatShellHelperIsRegistered();
    $this->expectsThatShellHelperGetsShell();
    $this->expectsThatDrupalShellIsRun();
    $this->expectsThatInpuHasShellParameters();

    $application = new Application();
    $application->setAutoExit(false);
    $application->setHelperSet($this->helperSet);

    $this->assertEquals(0, $application->run($this->input, new NullOutput()));
  }

  public function testCanRunDrupalCommand() {
    $this->expectsThatDrupalBootstrapHelperIsRegistered();
    $this->expectsThatBootstrapFinderHelperIsRegistered();
    $this->expectsThatKernelHelperIsRegistered();
    $this->expectsThatKernelHelperIsRetrievedToGetDrupalKernelConfigured();
    $this->expectsThatKernelHelperIsCalledToConfigureDrupalKernel();
    $this->expectsThatRegisterCommandsIsCalled();
    $this->expectsThatDrupalConsoleRegisterCommands();

    $this->expectsThatDrupalCommandIsRun();
    $this->expectsThatInputFirstArgumentIsGenerateModuleCommand();
    $this->expectsThatRunningACommandTriggersTheDispatcher();

    $application = new Application();
    $application->setAutoExit(false);
    $application->setHelperSet($this->helperSet);
    $application->add($this->command);

    $this->assertEquals(0, $application->run($this->input, new NullOutput()));
  }

  protected function expectsThatDrupalBootstrapHelperIsRegistered() {
    $this->helperSet->expects($this->at(1))
                    ->method('get')
                    ->with('bootstrap')
                    ->will($this->returnValue($this->drupalBootstrap));
  }

  protected function expectsThatBootstrapFinderHelperIsRegistered() {
    $this->helperSet->expects($this->at(2))
                    ->method('get')
                    ->with('finder')
                    ->will($this->returnValue($this->bootstrapFinder));
  }

  protected function expectsThatKernelHelperIsRegistered() {
    $this->helperSet->expects($this->at(3))
                    ->method('get')
                    ->with('kernel')
                    ->will($this->returnValue($this->kernel));
  }

  protected function expectsThatKernelHelperIsRetrievedToGetDrupalKernelConfigured() {
    $this->helperSet->expects($this->at(4))
                    ->method('get')
                    ->with('kernel')
                    ->will($this->returnValue($this->kernel));
  }

  protected function expectsThatKernelHelperIsCalledToConfigureDrupalKernel() {
    $this->kernel->expects($this->once())
                 ->method('bootKernel');
    $this->kernel->expects($this->once())
                ->method('initCommands');
    $this->kernel->expects($this->once())
                 ->method('getEventDispatcher')
                 ->will($this->returnValue($this->eventDispatcher));
  }

  protected function expectsThatRegisterCommandsIsCalled(){
    $this->helperSet->expects($this->at(5))
                    ->method('get')
                    ->with('register_commands')
                    ->will($this->returnValue($this->register_commands));
  }

  protected function expectsThatShellHelperIsRegistered() {
    $this->helperSet->expects($this->at(6))
                    ->method('get')
                    ->with('shell')
                    ->will($this->returnValue($this->shellHelper));
  }

  protected function expectsThatShellHelperGetsShell() {
    $this->shellHelper->expects($this->once())
                      ->method('getShell')
                      ->will($this->returnValue($this->shell));
  }

  protected function expectsThatDrupalShellIsRun() {
    $this->shell->expects($this->once())
                ->method('setProcessIsolation');
    $this->shell->expects($this->once())
                ->method('run');
  }

  protected function expectsThatDrupalConsoleRegisterCommands(){
    $this->register_commands->expects($this->once())
                            ->method('register');
  }

  protected function expectsThatInpuHasShellParameters() {
    $this->input->expects($this->at(17))
                ->method('hasParameterOption')
                ->with(array('--shell', '-s'))
                ->will($this->returnValue(true));
    $this->input->expects($this->at(18))
                ->method('hasParameterOption')
                ->with(array('--process-isolation'));
  }

  protected function expectsThatDrupalCommandIsRun() {
    $this->command->expects($this->once())
                  ->method('isEnabled')
                  ->will($this->returnValue(true));

    $this->command->expects($this->any())
                  ->method('getAliases')
                  ->will($this->returnValue(array()));

    $this->command->expects($this->any())
                  ->method('getName')
                  ->will($this->returnValue('generate:module'));

    $this->command->expects($this->once())
                  ->method('run')
                  ->will($this->returnValue(0));
  }

  protected function expectsThatInputFirstArgumentIsGenerateModuleCommand() {
    $this->input->expects($this->once())
                ->method('getFirstArgument')
                ->will($this->returnValue('generate:module'));
  }

  protected function expectsThatRunningACommandTriggersTheDispatcher() {
    $this->dispatcher->expects($this->any())
                     ->method('dispatch');
  }
}
