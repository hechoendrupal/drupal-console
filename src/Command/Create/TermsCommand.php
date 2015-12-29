<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\TermsCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class TermsCommand
 * @package Drupal\Console\Command\Generate
 */
class TermsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:terms')
            ->setDescription($this->trans('commands.create.terms.description'))
            ->addArgument(
                'vocabularies',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.terms.arguments.vocabularies')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.terms.options.limit')
            )
            ->addOption(
                'name-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.terms.options.name-words')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --content type argument
        $vocabularies = $input->getArgument('vocabularies');
        if (!$vocabularies) {
            $vocabularies = $this->getDrupalApi()->getVocabularies();
            $vids = $io->choice(
                $this->trans('commands.create.terms.questions.vocabularies'),
                array_values($vocabularies),
                null,
                true
            );

            $vids = array_map(
                function ($vid) use ($vocabularies) {
                    return array_search($vid, $vocabularies);
                },
                $vids
            );

            $input->setArgument('vocabularies', $vids);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.terms.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $nameWordsMin = $input->getOption('name-words');
        if (!$nameWordsMin) {
            $nameWordsMin = $io->ask(
                $this->trans('commands.create.terms.questions.name-words'),
                5
            );

            $input->setOption('name-words', $nameWordsMin);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $vocabularies = $this->getDrupalApi()->getVocabularies();

        // Validate provided vocabularies
        $vids = $input->getArgument('vocabularies');

        $invalidVids = array_filter(array_map(
            function ($vid) use ($vocabularies) {
                if(!isset($vocabularies[$vid])) {
                    return $vid;
                } else {
                    return null;
                }
            },
            $vids
        ));

        if(!empty($invalidVids)) {
            $io->error(
                sprintf(
                    $this->trans('commands.create.terms.messages.invalid-vocabularies'),
                    implode(',', $invalidVids)
                )
            );
            return;
        }

        $limit = $input->getOption('limit')?:10;
        $nameWords = $input->getOption('name-words')?:5;


        $createTerms = $this->getDrupalApi()->getCreateTerms();
        $terms = $createTerms->createTerm(
            $vids,
            $limit,
            $nameWords
        );

        $tableHeader = [
          $this->trans('commands.create.terms.messages.term-id'),
          $this->trans('commands.create.terms.messages.vocabulary'),
          $this->trans('commands.create.terms.messages.name'),
        ];

        $io->table($tableHeader, $terms['success']);

        $io->success(
            sprintf(
                $this->trans('commands.create.terms.messages.created-terms'),
                $limit
            )
        );

        return;
    }

    /**
     * @return array
     */
    private function getTimeRange()
    {
        $timeRanges = [
            1 => sprintf('N | %s', $this->trans('commands.create.nodes.questions.time-ranges.0')),
            3600 => sprintf('H | %s', $this->trans('commands.create.nodes.questions.time-ranges.1')),
            86400 => sprintf('D | %s', $this->trans('commands.create.nodes.questions.time-ranges.2')),
            604800 => sprintf('W | %s', $this->trans('commands.create.nodes.questions.time-ranges.3')),
            2592000 => sprintf('M | %s', $this->trans('commands.create.nodes.questions.time-ranges.4')),
            31536000 => sprintf('Y | %s', $this->trans('commands.create.nodes.questions.time-ranges.5'))
        ];

        return $timeRanges;
    }
}
