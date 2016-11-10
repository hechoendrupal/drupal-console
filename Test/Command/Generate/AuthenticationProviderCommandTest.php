<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorAuthenticationProviderCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\AuthenticationProviderCommand;
use Drupal\Console\Command\Generate\Questions\AuthenticationProviderQuestions;
use Drupal\Console\Command\Generate\Questions\ConfirmGeneration;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Test\Command\GenerateCommandTest;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\AuthenticationProviderDataProviderTrait;

class AuthenticationProviderCommandTest extends GenerateCommandTest
{
    use AuthenticationProviderDataProviderTrait;

    /**
     * AuthenticationProvider generator test
     *
     * @param string $module
     * @param string $class
     * @param int $providerId
     *
     * @dataProvider commandData
     */
    public function testGenerateAuthenticationProvider(
        $module,
        $class,
        $providerId
    ) {
        $generator = an::authenticationProviderGenerator();
        $confirmation = $this->prophesize(ConfirmGeneration::class);
        $confirmation->confirm()->willReturn(true);

        $command = new AuthenticationProviderCommand(
            $generator->reveal(),
            $this->prophesize(AuthenticationProviderQuestions::class)->reveal(),
            $confirmation->reveal()
        );

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--class' => $class,
                '--provider-id' => $providerId,
            ],
            ['interactive' => false]
        );

        $generator
            ->generate($module, $class, $providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }
}
