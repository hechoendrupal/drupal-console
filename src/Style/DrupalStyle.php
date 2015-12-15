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
    /**
     * @var InputInterface
     */
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

    /**
     * @param string $question
     * @param array  $choices
     * @param mixed  $default
     * @param bool   $allowEmpty
     *
     * @return string
     */
    public function choiceNoList($question, array $choices, $default = null, $allowEmpty = false)
    {
        if ($allowEmpty) {
            $default = ' ';
        }

        if (is_null($default)) {
            $default = current($choices);
        }

        if (!in_array($default, $choices)) {
            $choices[] = $default;
        }

        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        return trim($this->askChoiceQuestion(new ChoiceQuestion($question, $choices, $default)));
    }

    /**
     * @param ChoiceQuestion $question
     *
     * @return string
     */
    public function askChoiceQuestion(ChoiceQuestion $question)
    {
        $questionHelper = new DrupalChoiceQuestionHelper();
        $answer = $questionHelper->ask($this->input, $this, $question);

        return $answer;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function askHiddenEmpty($question)
    {
        $question = new Question($question, ' ');
        $question->setHidden(true);

        return trim($this->askQuestion($question));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function askEmpty($question)
    {
        $question = new Question($question, ' ');

        return trim($this->askQuestion($question));
    }

    /**
     * {@inheritdoc}
     */
    public function info($message)
    {
        $this->writeln(sprintf('<info> %s</info>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function comment($message)
    {
        $this->writeln(sprintf('<comment> %s</comment>', $message));
    }
}
