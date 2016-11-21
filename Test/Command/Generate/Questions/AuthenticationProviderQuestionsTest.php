<?php
/**
 * PHP version 5.5
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
use Symfony\Component\Console\Input\InputInterface;

class AuthenticationProviderQuestionsTest extends TestCase
{
    /** @test */
    function it_fails_if_there_are_no_modules()
    {
        // Given
        $this->thereAreNoExtensions();

        $this->setExpectedException(Exception::class);

        $this->questions->askForModule();
    }

    /** @test */
    function it_adds_module_to_the_options_if_no_profile_is_asked()
    {
        // Given
        $this->thereAreModules();
        $this->userSelectsModuleFrom($this->modules);
        $doNotShowProfiles = false;

        $this->assertEquals(
            $this->module,
            $this->questions->askForModule($doNotShowProfiles)
        );
    }

    /** @test */
    function it_adds_module_to_the_options_if_profile_is_asked()
    {
        // Given
        $this->thereAreModulesAndProfiles();
        $this->userSelectsModuleFrom($this->allExtensions);

        $this->assertEquals($this->module, $this->questions->askForModule());
    }

    /** @test */
    function it_fails_if_empty_class_is_given()
    {
        // Given
        $this->setExpectedException(Exception::class);
        $this->thereAreModulesAndProfiles();
        $this->userProvidesInvalidClassName();

        $this->questions->askForClass();
    }

    /** @test */
    function it_adds_class_when_valid_name_is_given()
    {
        // Given
        $this->thereAreModulesAndProfiles();
        $this->userProvidesValidClassName();

        $this->assertEquals($this->class, $this->questions->askForClass());
    }

    /** @test */
    function it_fails_if_empty_provider_id_is_given()
    {
        // Given
        $this->thereAreModulesAndProfiles();
        $this->userProvidesInvalidClassName();

        $this->setExpectedException(Exception::class);

        $this->questions->askForProviderId($this->input->reveal());
    }

    /** @test */
    function it_adds_provider_id_when_valid_name_is_given()
    {
        // Given
        $this->thereAreModulesAndProfiles();
        $this->thereIsValidOptionClass();
        $this->userProvidesValidClassName();

        $this->assertEquals(
            $this->class,
            $this->questions->askForProviderId($this->input->reveal())
        );
    }

    /**
     * @before
     */
    public function configure()
    {
        $this->configureCollaborators();
        $this->initValues();
        $this->createSUT();
    }

    private function configureCollaborators()
    {
        $this->extensionsManager = $this->prophesize(Manager::class);
        $this->style = $this->prophesize(DrupalStyle::class);
        $this->translator = $this->prophesize(TranslatorManager::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->stringConverter = new StringConverter();
    }

    private function initValues()
    {
        $this->module = 'console';
        $this->profile = 'admin';
        $this->class = 'Drupal\Console\Command\TestCommand';
        $this->modules = [$this->module];
        $this->profiles = [$this->profile];
        $this->allExtensions = [$this->module, $this->profile];
    }

    private function createSUT()
    {
        $this->questions = new AuthenticationProviderQuestions(
            $this->style->reveal(),
            $this->translator->reveal(),
            $this->extensionsManager->reveal(),
            $this->stringConverter
        );
    }

    private function thereAreNoExtensions()
    {
        $noExtensions = [];
        $noProfiles = [];
        $this
            ->extensionsManager
            ->showModuleNamesExceptCore()
            ->willReturn($noExtensions)
        ;
        $this
            ->extensionsManager
            ->showAllProfileNames()
            ->willReturn($noProfiles)
        ;
    }

    private function userSelectsModuleFrom($availableExtensions)
    {
        $this
            ->style
            ->choiceNoList(Argument::any(), $availableExtensions)
            ->willReturn($this->module)
        ;
    }

    private function thereAreModulesAndProfiles()
    {
        $this->thereAreModules();
        $this->thereAreProfiles();
    }

    private function thereAreModules()
    {
        $this
            ->extensionsManager
            ->showModuleNamesExceptCore()
            ->willReturn($this->modules)
        ;
    }

    private function thereAreProfiles()
    {
        $this
            ->extensionsManager
            ->showAllProfileNames()
            ->willReturn($this->profiles)
        ;
    }

    private function userProvidesInvalidClassName()
    {
        $this
            ->style
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(Exception::class)
        ;
    }

    private function userProvidesValidClassName()
    {
        $this
            ->style
            ->ask(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->class)
        ;
    }

    private function thereIsValidOptionClass()
    {
        $this->input->getOption('class')->willReturn($this->class);
    }

    /** @var string */
    private $class;

    /** @var array Extensions plus modules */
    private $allExtensions;

    /** @var string */
    private $module;

    /** @var string */
    private $profile;

    /** @var array */
    private $profiles;

    /** @var array */
    private $modules;

    /** @var AuthenticationProviderQuestions */
    private $questions;

    /** @var InputInterface */
    private $input;

    /** @var StringConverter */
    private $stringConverter;

    /** @var TranslatorManager */
    private $translator;

    /** @var Manager */
    private $extensionsManager;

    /** @var DrupalStyle */
    private $style;
}
