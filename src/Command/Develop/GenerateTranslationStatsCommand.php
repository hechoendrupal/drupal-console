<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateTranslationStatsCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class GenerateTranslationStatsCommand extends Command
{
    protected $statistics;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:translation:stats')
            ->setDescription($this->trans('commands.generate.translation.stats.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.generate.translation.stats.arguments.language'),
                'default'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $nestedArray = $this->getNestedArrayHelper();
        $englishFilesFinder = new Finder();
        $languagesFinder = new Finder();
        $yaml = new Parser();

        $language = null;
        if ($input->hasArgument('language')) {
            $language = $input->getArgument('language');
        }

        $application = $this->getApplication();
        $appRoot = $application->getDirectoryRoot();
        $englishDirectory = $appRoot . 'config/translations/en';

        $languages = $englishFilesFinder->directories()->in($appRoot . 'config/translations')->exclude('en');
        $englishFiles = $languagesFinder->files()->name('*.yml')->in($englishDirectory);

        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            try {
                $englishFileParsed = $yaml->parse(file_get_contents($resource));
                foreach ($languages as $languageDir) {
                    $langCode = $languageDir->getBasename();
                    if (isset($language) and $language != 'default' and $langCode != $language) {
                        continue;
                    }
                    if (!isset($this->statistics[$languageDir->getBasename()])) {
                        $this->statistics[$languageDir->getBasename()] = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
                    }
                    try {
                        $resourceTranslated = $languageDir . '/' . $file->getBasename();
                        if (file_exists($resourceTranslated)) {
                            $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));

                            $statistics = ['total' => 0, 'equal' => 0, 'diff' => 0];
                            $nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, false, $statistics);

                            $this->statistics[$langCode]['total'] += $statistics['total'];
                            $this->statistics[$langCode]['equal'] += $statistics['equal'];
                            $this->statistics[$langCode]['diff'] += $statistics['diff'];
                        }
                    } catch (ParseException $e) {
                        print $resourceTranslated . ' ' . $e->getMessage();
                    }
                }
            } catch (ParseException $e) {
                print $filename . '.yml ' . $e->getMessage();
            }
        }

        $languageNames = ['ca' => 'Catalan', 'es' => 'Spanish', 'fr' => 'French', 'hi' => 'Hindi', 'hu' => 'Hungarian', 'ja' => 'Japanese', 'pt_br' => 'Portuguese', 'ro' => 'Romanian', 'ru' => 'Russian', 'vn' => 'Vietnamese', 'zh_hans' => 'Chinese' ];

        $total = [];
        foreach ($this->statistics as $langCode => $statistic) {
            $index = isset($languageNames[$langCode])? $languageNames[$langCode]: $langCode;
            $total[$index] = round($statistic['diff']/$statistic['total']*100, 2);
        }

        arsort($total);

        print_r($total);
    }
}
