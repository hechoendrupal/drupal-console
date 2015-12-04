<?php

namespace Drupal\Console\Style;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drupal\Console\Helper\DrupalChoiceQuestionHelper;

class DrupalStyle extends SymfonyStyle
{
    private $input;

    /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        parent::__construct($input, $output);
    }

    public function choiceNoList($question, array $choices, $default = null)
    {
        if (is_null($default)) {
            $values = array_flip($choices);
            $default = current($values);
        }
        //


        return $this->askChoiceQuestion(new ChoiceQuestion($question, $choices, $default));
    }

    /**
   * @param Question $question
   *
   * @return string
   */
    public function askChoiceQuestion(Question $question)
    {
        $questionHelper = new DrupalChoiceQuestionHelper();
        $answer = $questionHelper->ask($this->input, $this, $question);

        return $answer;
    }
}
