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

class AuthenticationProviderCommandTest extends GenerateCommandTest
{
    /** @test */
    public function it_generates_an_authentication_provider_without_interaction()
    {
        // Given
        $this->userConfirmsGeneration();

        // When
        $code = $this->tester->execute(
            $this->withAllOptions(),
            $this->nonInteractive
        );

        // Then
        $this
            ->generator
            ->generate($this->module, $this->class, $this->providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @test */
    public function it_ask_for_a_module_if_none_is_provided()
    {
        // Given
        $this->userConfirmsGeneration();
        $this->userProvidesModule();

        // When
        $code = $this->tester->execute(
            $this->withoutModule(),
            $this->interactive
        );

        // Then
        $this
            ->generator
            ->generate($this->module, $this->class, $this->providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @test */
    public function it_ask_for_a_class_if_none_is_provided()
    {
        // Given
        $this->userConfirmsGeneration();
        $this->userProvidesClass();

        // When
        $code = $this->tester->execute(
            $this->withoutClass(),
            $this->interactive
        );

        // Then
        $this
            ->generator
            ->generate($this->module, $this->class, $this->providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @test */
    public function it_ask_for_a_provider_if_none_is_provided()
    {
        // Given
        $this->userConfirmsGeneration();
        $this->userInputsProviderId();

        // When
        $code = $this->tester->execute(
            $this->withoutProviderId(),
            $this->interactive
        );

        // Then
        $this
            ->generator
            ->generate($this->module, $this->class, $this->providerId)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }

    /** @before */
    public function configure()
    {
        $this->configureCollaborators();
        $this->configureSUT();
    }

    private function configureCollaborators()
    {
        $this->generator = an::authenticationProviderGenerator();
        $this->questions = $this->prophesize(AuthenticationProviderQuestions::class);
        $this->confirmation = $this->prophesize(ConfirmGeneration::class);
    }

    private function configureSUT()
    {
        $this->command = new AuthenticationProviderCommand(
            $this->generator->reveal(),
            $this->questions->reveal(),
            $this->confirmation->reveal()
        );
        $this->tester = new CommandTester($this->command);
    }

    /** @return array */
    private function withAllOptions()
    {
        return [
            '--module' => $this->module,
            '--class' => $this->class,
            '--provider-id' => $this->providerId,
        ];
    }

    /** @return array */
    private function withoutModule()
    {
        return [
            '--class' => $this->class,
            '--provider-id' => $this->providerId,
        ];
    }

    /** @return array */
    private function withoutClass()
    {
        return [
            '--module' => $this->module,
            '--provider-id' => $this->providerId,
        ];
    }

    /** @return array */
    private function withoutProviderId()
    {
        return [
            '--module' => $this->module,
            '--class' => $this->class,
        ];
    }

    private function userConfirmsGeneration()
    {
        $this->confirmation->confirm()->willReturn(true);
    }

    private function userProvidesModule()
    {
        $this->questions->askForModule()->willReturn($this->module);
    }

    private function userProvidesClass()
    {
        $this->questions->askForClass()->willReturn($this->class);
    }

    private function userInputsProviderId()
    {
        $this
            ->questions
            ->askForProviderId(Argument::any())
            ->willReturn($this->providerId)
        ;
    }

    /** @var \Drupal\Console\Generator\AuthenticationProviderGenerator */
    private $generator;

    /** @var AuthenticationProviderQuestions */
    private $questions;

    /** @var ConfirmGeneration */
    private $confirmation;

    /** @var AuthenticationProviderCommand */
    private $command;

    /** @var CommandTester */
    private $tester;

    private $module = 'module name';
    private $class = 'Console\Classname';
    private $providerId = 'Console\Classname';
    private $nonInteractive = ['interactive' => false];
    private $interactive = ['interactive' => true];
}
