<?php
/**
 * PHP version 5.5
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace Command\Generate\Questions;

use Drupal\Console\Command\Generate\Questions\ConfirmGeneration;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\TranslatorManager;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class ConfirmGenerationTest extends TestCase
{
    /** @test */
    function it_confirms_positively_when_default_value_is_set_to_true()
    {
        $this->assertTrue($this->confirm->confirm(true));
    }

    /** @test */
    function it_confirms_when_user_answer_is_positive()
    {
        // Given
        $this->userConfirmsGeneration();

        $this->assertTrue($this->confirm->confirm());
    }

    /** @test */
    function it_shows_a_warning_if_answer_is_negative()
    {
        // Given
        $this->userCancelsGeneration();
        // Then
        $this->userGetsCancellationWarning();

        $this->assertFalse($this->confirm->confirm());
    }

    /** @before */
    public function configure()
    {
        $this->configureCollaborators();
        $this->configureSUT();
    }

    private function configureCollaborators()
    {
        $this->style = $this->prophesize(DrupalStyle::class);
        $this->translator = $this->prophesize(TranslatorManager::class);
    }

    private function configureSUT()
    {
        $this->confirm = new ConfirmGeneration(
            $this->style->reveal(),
            $this->translator->reveal()
        );
    }

    private function userConfirmsGeneration()
    {
        $confirm = true;
        $this->style->confirm(Argument::any(), true)->willReturn($confirm);
    }

    private function userCancelsGeneration()
    {
        $confirm = false;
        $this->style->confirm(Argument::any(), true)->willReturn($confirm);
    }

    private function userGetsCancellationWarning()
    {
        $this->style->warning(Argument::any())->shouldBeCalled();
    }

    /** @var ConfirmGeneration */
    private $confirm;

    /** @var DrupalStyle */
    private $style;

    /** @var TranslatorManager */
    private $translator;
}
