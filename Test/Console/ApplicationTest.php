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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->helperSet = $this
          ->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
          ->getMock();

        $this->drupalAutoload = $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\DrupalAutoloadHelper')
          ->disableOriginalConstructor()
          ->setMethods(['findAutoload', 'getDrupalRoot'])
          ->getMock();

        $this->register_commands = $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\RegisterCommandsHelper')
          ->disableOriginalConstructor()
          ->getMock();
    }

    public function testCanRunApplication()
    {
        $this->expectsThatAutoloadFinderHelperIsRegistered();

        $config = $this
          ->getMockBuilder('Drupal\AppConsole\Config')
          ->disableOriginalConstructor()
          ->getMock();

        $application = new Application($config);
        $application->setAutoExit(false);
        $application->setHelperSet($this->helperSet);
        $application->setSearchSettingsFile(false);

        $this->assertEquals(0, $application->run(new ArrayInput([]), new NullOutput()));
    }

    protected function expectsThatAutoloadFinderHelperIsRegistered()
    {
        $this->helperSet->expects($this->at(1))
          ->method('get')
          ->with('drupal-autoload')
          ->will($this->returnValue($this->drupalAutoload));
    }
}
