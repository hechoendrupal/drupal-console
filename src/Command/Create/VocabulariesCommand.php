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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class VocabulariesCommand
 * @package Drupal\Console\Command\Generate
 */
class VocabulariesCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:vocabularies')
            ->setDescription($this->trans('commands.create.vocabularies.description'))
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.vocabularies.options.limit')
            )
            ->addOption(
                'name-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.vocabularies.options.name-words')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.vocabularies.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $nameWords = $input->getOption('name-words');
        if (!$nameWords) {
            $nameWords = $io->ask(
                $this->trans('commands.create.vocabularies.questions.name-words'),
                5
            );

            $input->setOption('name-words', $nameWords);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $limit = $input->getOption('limit')?:25;
        $nameWords = $input->getOption('name-words')?:5;

        $createVocabularies = $this->getApplication()->getDrupalApi()->getCreateVocabularies();
        $vocabularies = $createVocabularies->createVocabulary(
            $limit,
            $nameWords
        );

        $tableHeader = [
          $this->trans('commands.create.vocabularies.messages.vocabulary-id'),
          $this->trans('commands.create.vocabularies.messages.name'),
        ];

        if (isset($vocabularies['success'])) {
            $io->table($tableHeader, $vocabularies['success']);

            $io->success(
                sprintf(
                    $this->trans('commands.create.vocabularies.messages.created-terms'),
                    $limit
                )
            );
        } else {
            $io->error(
                sprintf(
                    $this->trans('commands.create.vocabularies.messages.error'),
                    $vocabularies['error'][0]['error']
                )
            );
        }

        return;
    }
}
