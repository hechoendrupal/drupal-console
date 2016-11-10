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
        $confirm = new ConfirmGeneration(
            $this->prophesize(DrupalStyle::class)->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal()
        );
        $this->assertTrue($confirm->confirm(true));
    }

    /** @test */
    function it_confirms_when_user_answer_is_positive()
    {
        $question = $this->prophesize(DrupalStyle::class);
        $question->confirm(Argument::any(), true)->willReturn(true);

        $confirm = new ConfirmGeneration(
            $question->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal()
        );

        $this->assertTrue($confirm->confirm());
    }

    /** @test */
    function it_shows_a_warning_if_answer_is_negative()
    {
        $question = $this->prophesize(DrupalStyle::class);
        $question->confirm(Argument::any(), true)->willReturn(false);
        $question->warning(Argument::any())->shouldBeCalled();

        $confirm = new ConfirmGeneration(
            $question->reveal(),
            $this->prophesize(TranslatorManager::class)->reveal()
        );

        $this->assertFalse($confirm->confirm());
    }
}
