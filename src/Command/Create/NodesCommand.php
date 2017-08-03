<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\NodesCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Utils\Create\NodeData;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class NodesCommand
 *
 * @package Drupal\Console\Command\Generate
 *
 * @DrupalCommand(
 *     extension = "node",
 *     extensionType = "module"
 * )
 */
class NodesCommand extends Command
{
    use CreateTrait;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;
    /**
     * @var NodeData
     */
    protected $createNodeData;

    /**
     * NodesCommand constructor.
     *
     * @param DrupalApi $drupalApi
     * @param NodeData  $createNodeData
     */
    public function __construct(
        DrupalApi $drupalApi,
        NodeData $createNodeData
    ) {
        $this->drupalApi = $drupalApi;
        $this->createNodeData = $createNodeData;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:nodes')
            ->setDescription($this->trans('commands.create.nodes.description'))
            ->addArgument(
                'content-types',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.nodes.arguments.content-types')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.time-range')
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.language')
            )->setAliases(['crn']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $contentTypes = $input->getArgument('content-types');
        if (!$contentTypes) {
            $bundles = $this->drupalApi->getBundles();
            $contentTypes = $io->choice(
                $this->trans('commands.create.nodes.questions.content-type'),
                array_values($bundles),
                null,
                true
            );

            $contentTypes = array_map(
                function ($contentType) use ($bundles) {
                    return array_search($contentType, $bundles);
                },
                $contentTypes
            );

            $input->setArgument('content-types', $contentTypes);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.nodes.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $titleWords = $input->getOption('title-words');
        if (!$titleWords) {
            $titleWords = $io->ask(
                $this->trans('commands.create.nodes.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWords);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.create.nodes.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range', array_search($timeRange, $timeRanges));
        }

        // Language module is enabled or not.
        $languageModuleEnabled = \Drupal::moduleHandler()
            ->moduleExists('language');

        // If language module is enabled.
        if ($languageModuleEnabled) {
            // Get available languages on site.
            $languages = \Drupal::languageManager()->getLanguages();
            // Holds the available languages.
            $language_list = [];

            foreach ($languages as $lang) {
                $language_list[$lang->getId()] = $lang->getName();
            }

            $language = $input->getOption('language');
            // If no language option or invalid language code in option.
            if (!$language || !array_key_exists($language, $language_list)) {
                $language = $io->choice(
                    $this->trans('commands.create.nodes.questions.language'),
                    $language_list
                );
            }
            $input->setOption('language', $language);
        } else {
            // If 'language' module is not enabled.
            $input->setOption('language', LanguageInterface::LANGCODE_NOT_SPECIFIED);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $contentTypes = $input->getArgument('content-types');
        $limit = $input->getOption('limit')?:25;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:31536000;
        $available_types = array_keys($this->drupalApi->getBundles());
        $language = $input->getOption('language')?:'und';

        foreach ($contentTypes as $type) {
            if (!in_array($type, $available_types)) {
                throw new \Exception('Invalid content type name given.');
            }
        }

        if (!$contentTypes) {
            $contentTypes = $available_types;
        }

        $nodes = $this->createNodeData->create(
            $contentTypes,
            $limit,
            $titleWords,
            $timeRange,
            $language
        );
        
        $nodes = is_array($nodes) ? $nodes : [$nodes];

        $tableHeader = [
          $this->trans('commands.create.nodes.messages.node-id'),
          $this->trans('commands.create.nodes.messages.content-type'),
          $this->trans('commands.create.nodes.messages.title'),
          $this->trans('commands.create.nodes.messages.created'),
        ];

        $io->table($tableHeader, $nodes['success']);

        $io->success(
            sprintf(
                $this->trans('commands.create.nodes.messages.created-nodes'),
                $limit
            )
        );

        return 0;
    }
}
