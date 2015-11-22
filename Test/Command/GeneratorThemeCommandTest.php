<?php
/**
 * @file
 * Contains Drupal\Console\Test\Command\GeneratorThemeCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\ThemeCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ThemeDataProviderTrait;

class GeneratorThemeCommandTest extends GenerateCommandTest
{
    use ThemeDataProviderTrait;
    
    /**
     * Theme generator test
     *
     * @param $theme
     * @param $machine_name
     * @param $theme_path
     * @param $description
     * @param $core
     * @param $package
     * @param $global_library
     * @param $base_theme
     * @param $regions
     * @param $breakpoints
     *
     * @dataProvider commandData
     */
    public function testGenerateTheme(
        $theme,
        $machine_name,
        $theme_path,
        $description,
        $core,
        $package,
        $global_library,
        $base_theme,
        $regions,
        $breakpoints
    ) {
        $command = new ThemeCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
            '--theme'           => $theme,
            '--machine-name'    => $machine_name,
            '--theme-path'      => $theme_path,
            '--description'     => $description,
            '--core'            => $core,
            '--package'         => $package,
            '--global-library'  => $global_library,
            '--base-theme'      => $base_theme,
            '--regions'         => $regions,
            '--breakpoints'     => $breakpoints
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\ThemeGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
