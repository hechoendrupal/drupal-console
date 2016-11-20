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
use Prophecy\Argument;
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

    /** @test */
    public function it_ask_for_a_module_if_none_is_provided()
    {
        $generator = an::authenticationProviderGenerator();
        $confirmation = $this->prophesize(ConfirmGeneration::class);
        $confirmation->confirm()->willReturn(true);

        $module = 'module name';

        $questions = $this->prophesize(AuthenticationProviderQuestions::class);
        $questions->askForModule()->willReturn($module);

        $command = new AuthenticationProviderCommand(
            $generator->reveal(),
            $questions->reveal(),
            $confirmation->reveal()
        );

        $class = 'Console\Classname';
        $providerId = 'Console\Classname';

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute(
            [
                '--class' => $class,
                '--provider-id' => $providerId,
            ],
            ['interactive' => true]
        );

        $generator
            ->generate($module, $class, $providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @test */
    public function it_ask_for_a_class_if_none_is_provided()
    {
        $generator = an::authenticationProviderGenerator();
        $confirmation = $this->prophesize(ConfirmGeneration::class);
        $confirmation->confirm()->willReturn(true);

        $class = 'Console\Classname';

        $questions = $this->prophesize(AuthenticationProviderQuestions::class);
        $questions->askForClass()->willReturn($class);

        $command = new AuthenticationProviderCommand(
            $generator->reveal(),
            $questions->reveal(),
            $confirmation->reveal()
        );

        $module = 'module name';
        $providerId = 'Console\Classname';

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--provider-id' => $providerId,
            ],
            ['interactive' => true]
        );

        $generator
            ->generate($module, $class, $providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @test */
    public function it_ask_for_a_provider_if_none_is_provided()
    {
        $generator = an::authenticationProviderGenerator();
        $confirmation = $this->prophesize(ConfirmGeneration::class);
        $confirmation->confirm()->willReturn(true);

        $providerId = 'Console\Classname';
        $questions = $this->prophesize(AuthenticationProviderQuestions::class);
        $questions
            ->askForProviderId(Argument::any())
            ->willReturn($providerId)
        ;

        $command = new AuthenticationProviderCommand(
            $generator->reveal(),
            $questions->reveal(),
            $confirmation->reveal()
        );

        $module = 'module name';
        $class = 'Console\Classname';

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--class' => $class,
            ],
            ['interactive' => true]
        );

        $generator
            ->generate($module, $class, $providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }
}
