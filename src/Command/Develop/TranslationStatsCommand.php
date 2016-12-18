<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\TranslationStatsCommand.
 */

namespace Drupal\Console\Command\Develop;

use Drupal\Console\Command\Shared\TranslationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Utils\TwigRenderer;
use Drupal\Console\Utils\NestedArray;

class TranslationStatsCommand extends Command
{
    use TranslationTrait;
    use CommandTrait;

    /**
     * @var string
     */
        protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var TwigRenderer $renderer
     */
    protected $renderer;

    /**
      * @var NestedArray
      */
    protected $nestedArray;

    /**
     * TranslationStatsCommand constructor.
     *
     * @param $appRoot
     * @param ConfigurationManager $configurationManager
     * @param TwigRenderer         $renderer
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager,
        TwigRenderer $renderer,
        NestedArray $nestedArray
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        $this->renderer = $renderer;
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */

    protected function configure()
    {
        $this
            ->setName('translation:stats')
            ->setDescription($this->trans('commands.translation.stats.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.translation.stats.arguments.language'),
                null
            )
            ->addOption(
                'format',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.translation.stats.options.format'),
                'table'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $language = $input->getArgument('language');
        $format = $input->getOption('format');

        $languages = $this->configurationManager->getConfiguration()->get('application.languages');
        unset($languages['en']);

        if ($language && !isset($languages[$language])) {
            $io->error(
                sprintf(
                    $this->trans('commands.translation.stats.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language) {
            $languages = [$language => $languages[$language]];
        }

        $stats = $this->calculateStats($io, $language, $languages);

        if ($format == 'table') {
            $tableHeaders = [
                $this->trans('commands.translation.stats.messages.language'),
                $this->trans('commands.translation.stats.messages.percentage'),
                $this->trans('commands.translation.stats.messages.iso')
            ];

            $io->table($tableHeaders, $stats);
            return 0;
        }

        if ($format == 'markdown') {
            $arguments['language'] = $this->trans('commands.translation.stats.messages.language');
            $arguments['percentage'] = $this->trans('commands.translation.stats.messages.percentage');

            $arguments['languages'] = $stats;

            $io->writeln(
                $this->renderFile(
                    'core/translation/stats.md.twig',
                    null,
                    $arguments
                )
            );
        }
    }

    protected function calculateStats($io, $language = null, $languages)
    {
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $statistics = [];

        $englishDirectory = $this->consoleRoot .
            sprintf(
                DRUPAL_CONSOLE_LANGUAGE,
                'en'
            );

        $englishFiles = $englishFilesFinder->files()->name('*.yml')->in($englishDirectory);

        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            try {
                $englishFileParsed = $yaml->parse(file_get_contents($resource));
            } catch (ParseException $e) {
                $io->error($filename . '.yml: ' . $e->getMessage());
                continue;
            }

            foreach ($languages as $langCode => $languageName) {
                $languageDir = $this->consoleRoot .
                                        sprintf(
                                            DRUPAL_CONSOLE_LANGUAGE,
                                            $langCode
                                        );
                                //don't show that language if that repo isn't present
                if (!file_exists($languageDir)) {
                    continue;
                }
                if (isset($language) && $langCode != $language) {
                    continue;
                }
                if (!isset($statistics[$langCode])) {
                    $statistics[$langCode] = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
                }

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                if (!file_exists($resourceTranslated)) {
                    $englishFileEntries = count($englishFileParsed, COUNT_RECURSIVE);
                    $statistics[$langCode]['total'] += $englishFileEntries;
                    $statistics[$langCode]['equal'] += $englishFileEntries;
                    continue;
                }

                try {
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                }

                $diffStatistics = ['total' => 0, 'equal' => 0, 'diff' => 0];
                $diff = $this->nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, true, $diffStatistics);

                $yamlKeys = 0;
                if (!empty($diff)) {
                    $diffFlatten = array();
                    $keyFlatten = '';
                    $this->nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

                    // Determine how many yaml keys were returned as values
                    foreach ($diffFlatten as $yamlKey => $yamlValue) {
                        if ($this->isYamlKey($yamlValue)) {
                            $yamlKeys++;
                        }
                    }
                }

                $statistics[$langCode]['total'] += $diffStatistics['total'];
                $statistics[$langCode]['equal'] += ($diffStatistics['equal'] - $yamlKeys);
                $statistics[$langCode]['diff'] += $diffStatistics['diff'] + $yamlKeys;
            }
        }

        $stats = [];
        foreach ($statistics as $langCode => $statistic) {
            $index = isset($languages[$langCode])? $languages[$langCode]: $langCode;
            $stats[] = [
                'name' => $index,
                'percentage' => round($statistic['diff']/$statistic['total']*100, 2),
                'iso' => $langCode
            ];
        }

        usort(
            $stats, function ($a, $b) {
                return $a["percentage"] <  $b["percentage"];

            }
        );

        return $stats;
    }
}
