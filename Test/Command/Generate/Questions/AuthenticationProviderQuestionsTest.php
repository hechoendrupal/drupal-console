<?php
/**
 * PHP version 7.0
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace Drupal\Console\Test\Command\Generate\Questions;

use Drupal\Console\Command\Generate\Questions\AuthenticationProviderQuestions;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\TranslatorManager;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class AuthenticationProviderQuestionsTest extends TestCase
{
    /** @test */
    function it_fails_if_there_are_no_modules()
    {
        $this->setExpectedException(Exception::class);

        $manager = $this->prophesize(Manager::class);
        $manager->showModuleNamesExceptCore()->willReturn([]);
        $manager->showAllProfileNames()->willReturn([]);

        $questions = new AuthenticationProviderQuestions(
            $this->prophesize(DrupalStyle::class)->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $questions->askForModule(new ArrayInput([]));
    }

    /** @test */
    function it_adds_module_to_the_options_if_no_profile_is_asked()
    {
        $manager = $this->prophesize(Manager::class);
        $module = 'console';
        $modules = [$module];
        $manager->showModuleNamesExceptCore()->willReturn($modules);

        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->choiceNoList(Argument::any(), $modules)
            ->willReturn($module)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $this->assertEquals($module, $questions->askForModule(false));
    }

    /** @test */
    function it_adds_module_to_the_options_if_profile_is_asked()
    {
        $manager = $this->prophesize(Manager::class);
        $module = 'console';
        $modules = [$module];
        $profile = 'admin';
        $profiles = [$profile];
        $manager->showModuleNamesExceptCore()->willReturn($modules);
        $manager->showAllProfileNames()->willReturn($profiles);

        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->choiceNoList(Argument::any(), [$module, $profile])
            ->willReturn($module)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $this->assertEquals($module, $questions->askForModule());
    }

    /** @test */
    function it_fails_if_empty_class_is_given()
    {
        $this->setExpectedException(Exception::class);

        $manager = $this->prophesize(Manager::class);
        $manager->showModuleNamesExceptCore()->willReturn(['console']);
        $manager->showAllProfileNames()->willReturn(['admin']);

        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(Exception::class)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $definition = new InputDefinition([new InputOption('class')]);
        $input = new ArrayInput(['--class' => '   '], $definition);

        $questions->askForClass($input);
    }

    /** @test */
    function it_adds_class_when_valid_name_is_given()
    {
        $manager = $this->prophesize(Manager::class);
        $manager->showModuleNamesExceptCore()->willReturn(['console']);
        $manager->showAllProfileNames()->willReturn(['admin']);

        $class = 'Drupal\Console\Command\TestCommand';
        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($class)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $this->assertEquals($class, $questions->askForClass());
    }

    /** @test */
    function it_fails_if_empty_provider_id_is_given()
    {
        $this->setExpectedException(Exception::class);

        $manager = $this->prophesize(Manager::class);
        $manager->showModuleNamesExceptCore()->willReturn(['console']);
        $manager->showAllProfileNames()->willReturn(['admin']);

        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(Exception::class)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $definition = new InputDefinition([new InputOption('provider-id')]);
        $input = new ArrayInput(['--provider-id' => '   '], $definition);

        $questions->askForProviderId($input);
    }

    /** @test */
    function it_adds_class_when_valid_provider_id_is_given()
    {
        $manager = $this->prophesize(Manager::class);
        $manager->showModuleNamesExceptCore()->willReturn(['console']);
        $manager->showAllProfileNames()->willReturn(['admin']);

        $class = 'Drupal\Console\Command\TestCommand';
        $io = $this->prophesize(DrupalStyle::class);
        $io
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($class)
        ;

        $questions = new AuthenticationProviderQuestions(
            $io->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal(),
            $manager->reveal(),
            new StringConverter()
        );

        $this->assertEquals($class, $questions->askForClass());
    }
}
