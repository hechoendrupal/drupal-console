<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\TermsCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Create\VocabularyData;

/**
 * Class VocabulariesCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class VocabulariesCommand extends Command
{
    /**
     * @var VocabularyData
     */
    protected $vocabularyData;

    /**
     * UsersCommand constructor.
     *
     * @param $vocabularyData
     */
    public function __construct(VocabularyData $vocabularyData)
    {
        $this->vocabularyData = $vocabularyData;
        parent::__construct();
    }

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
            )->setAliases(['crv']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->getIo()->ask(
                $this->trans('commands.create.vocabularies.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $nameWords = $input->getOption('name-words');
        if (!$nameWords) {
            $nameWords = $this->getIo()->ask(
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
        $limit = $input->getOption('limit')?:25;
        $nameWords = $input->getOption('name-words')?:5;

        $result = $this->vocabularyData->create(
            $limit,
            $nameWords
        );

        $tableHeader = [
          $this->trans('commands.create.vocabularies.messages.vocabulary-id'),
          $this->trans('commands.create.vocabularies.messages.name'),
        ];

        if (isset($result['success'])) {
            $this->getIo()->table($tableHeader, $result['success']);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.create.vocabularies.messages.created-terms'),
                    count($result['success'])
                )
            );
        }

        if (isset($result['error'])) {
            foreach ($result['error'] as $error) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.create.vocabularies.messages.error'),
                        $error
                    )
                );
            }
        }

        return 0;
    }
}
